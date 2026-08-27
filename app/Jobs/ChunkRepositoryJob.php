<?php

namespace App\Jobs;

use App\Models\CodeChunk;
use App\Models\RepositoryIndexation;
use App\Models\RepositorySource;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChunkRepositoryJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;
    public int $tries=3;
    public int $backoff=15;
    public int $timeout=300;

    private const CHUNK_SIZE_TOKENS = 400;
    private const CHUNK_OVERLAP_TOKENS = 50;
    private const CHARS_PER_TOKEN = 4; 

    /**
     * Create a new job instance.
     */
    public function __construct(public RepositorySource $source)
    {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $content =Cache::get(SyncRepositoryJob::contentCacheKey($this->source->id));
        if($content===null){
            Log::warning("ChunkRepositoryJob: content not found in cache for source {$this->source->id} (probably expired)");
            return;
        }
        $this->source->codeChunks()->delete();
        $chunks=$this->splitIntoChunks($content);
        $created=0;
        foreach($chunks as $index => $chunkContent){
            CodeChunk::create([
                'repository_source_id'=>$this->source->id,
                'chunk_content'=>$chunkContent['content'],
                'token_count'=>$chunkContent['token_count'],
                'chunk_index'=>$index,
                'metadata'=>[
                    'start_line'=>$chunkContent['start_line'],
                    'end_line'=>$chunkContent['end_line'],
                    'source_path'=>$this->source->source_identifier,
                    'language'=>$this->source->language,
                ],
                'embedding_status'=>'pending',
            ]);
            $created++;
        } 
        $this->source->update(['last_indexed_at'=>now()]);
        Cache::forget(SyncRepositoryJob::contentCacheKey($this->source->id));
        RepositoryIndexation::where('repository_id',$this->source->repository_id)
            ->where('status','running')
            ->latest('started_at')
            ->first()
            ?->incrementProgress(sourcesProcessed:0,chunksCreated:$created);
        Log::info("ChunkRepositoryJob: {$created} chunks created for {$this->source->source_identifier}");
        GenerateEmbeddingsJob::dispatch($this->source);

    }


    public function splitIntoChunks(string $content):array{
        $lines=explode("\n",$content);
        $totalLines=count($lines);
        if($totalLines===0){
            return [];
        }
        $chunks=[];
        $currentLineIndex=0;
        while($currentLineIndex<$totalLines){
            $chunkLines=[];
            $chunkCharCount=0;
            $startLine=$currentLineIndex;
            while($currentLineIndex<$totalLines){
                $line=$lines[$currentLineIndex];
                $lineLength=mb_strlen($line)+1;
                if($chunkCharCount>0 && ($chunkCharCount+$lineLength)>(self::CHUNK_SIZE_TOKENS*self::CHARS_PER_TOKEN)){
                    break;  
                }
                $chunkLines[]=$line;
                $chunkCharCount+=$lineLength;
                $currentLineIndex++;
            }
            if(empty($chunkLines) && $currentLineIndex<$totalLines){
                $chunkLines[]=$lines[$currentLineIndex];
                $currentLineIndex++;
            }
            $endLine=$currentLineIndex-1;
            $chunkContent=implode("\n",$chunkLines);
            $chunks[]=[
                'content'=>$chunkContent,
                'token_count'=>(int)ceil(mb_strlen($chunkContent)/self::CHARS_PER_TOKEN),
                'start_line'=>$startLine+1,
                'end_line'=>$endLine+1,
            ];
            if($currentLineIndex<$totalLines){
                $overlapChars=self::CHUNK_OVERLAP_TOKENS*self::CHARS_PER_TOKEN;
                $rewindChars=0;
                $rewindLines=0;
                for($i=$currentLineIndex-1;$i>=$startLine;$i--){
                    $rewindChars+=mb_strlen($lines[$i])+1;
                    $rewindLines++;
                    if($rewindChars>=$overlapChars){
                        break;
                    }
                }
                $currentLineIndex-=$rewindLines;
            }
        }
        return $chunks;
    }

    public function failed(Throwable $exception): void
    {
        Log::error("ChunkRepositoryJob failed for source {$this->source->id}: {$exception->getMessage()}");
    }
}
