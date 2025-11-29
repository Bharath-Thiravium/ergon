<?php

namespace Ergon\FinanceSync;

use Psr\Log\LoggerInterface;

class SyncService
{
    private SourceRepo $sourceRepo;
    private TargetRepo $targetRepo;
    private LoggerInterface $logger;
    private array $config;
    
    public function __construct(
        SourceRepo $sourceRepo,
        TargetRepo $targetRepo,
        LoggerInterface $logger,
        array $config
    ) {
        $this->sourceRepo = $sourceRepo;
        $this->targetRepo = $targetRepo;
        $this->logger = $logger;
        $this->config = $config;
    }
    
    /**
     * Execute the sync process
     */
    public function sync(
        string $companyPrefix,
        bool $fullLoad = false,
        ?int $limit = null
    ): int {
        $startedAt = date('Y-m-d H:i:s');
        $totalFetched = 0;
        $totalUpserted = 0;
        $totalErrors = 0;
        
        try {
            $this->logger->info("Starting sync", [
                'company_prefix' => $companyPrefix,
                'full_load' => $fullLoad,
                'limit' => $limit
            ]);
            
            // Create tables if needed
            $this->targetRepo->createTablesIfNotExist();
            
            // Get last sync timestamp for incremental sync
            $lastSyncAt = null;
            if (!$fullLoad) {
                $lastSyncAt = $this->targetRepo->getLastSyncTimestamp(
                    $companyPrefix,
                    $this->config['sync_table']
                );
            }
            
            // Process in batches
            $batchSize = $this->config['batch_size'];
            $offset = 0;
            $maxTimestamp = null;
            
            do {
                $currentLimit = $limit ? min($batchSize, $limit - $totalFetched) : $batchSize;
                
                // Fetch batch from source
                $rows = $this->sourceRepo->fetchInvoiceRows(
                    $companyPrefix,
                    $fullLoad,
                    $lastSyncAt,
                    $currentLimit
                );
                
                if (empty($rows)) {
                    break;
                }
                
                $totalFetched += count($rows);
                
                // Transform rows
                $transformedRows = [];
                foreach ($rows as $row) {
                    try {
                        $transformedRows[] = Transformer::transformInvoiceRow($row, $companyPrefix);
                    } catch (\Exception $e) {
                        $this->logger->error("Transform failed for row", [
                            'document_number' => $row['invoice_number'] ?? 'unknown',
                            'error' => $e->getMessage()
                        ]);
                        $totalErrors++;
                    }
                }
                
                // Upsert batch
                if (!empty($transformedRows)) {
                    $result = $this->targetRepo->upsertBatch($transformedRows, $companyPrefix);
                    $totalUpserted += $result['upserted'];
                    $totalErrors += count($result['errors']);
                }
                
                // Track max timestamp for incremental sync
                $batchMaxTimestamp = $this->sourceRepo->getMaxTimestamp($rows);
                if ($batchMaxTimestamp && ($maxTimestamp === null || $batchMaxTimestamp > $maxTimestamp)) {
                    $maxTimestamp = $batchMaxTimestamp;
                }
                
                $offset += $batchSize;
                
                // Break if we've hit the limit
                if ($limit && $totalFetched >= $limit) {
                    break;
                }
                
            } while (count($rows) === $currentLimit);
            
            // Update last sync timestamp if we have new data
            if ($maxTimestamp && !$fullLoad) {
                $this->targetRepo->updateLastSyncTimestamp(
                    $companyPrefix,
                    $maxTimestamp,
                    $this->config['sync_table']
                );
            }
            
            $endedAt = date('Y-m-d H:i:s');
            $status = $totalErrors > 0 ? 'partial_failure' : 'success';
            
            // Log sync run
            $this->targetRepo->logSyncRun(
                $companyPrefix,
                $startedAt,
                $endedAt,
                $totalFetched,
                $totalUpserted,
                $totalErrors,
                $status
            );
            
            $this->logger->info("Sync completed", [
                'company_prefix' => $companyPrefix,
                'status' => $status,
                'fetched' => $totalFetched,
                'upserted' => $totalUpserted,
                'errors' => $totalErrors,
                'duration' => strtotime($endedAt) - strtotime($startedAt) . 's'
            ]);
            
            return $totalErrors > 0 ? 1 : 0;
            
        } catch (\Exception $e) {
            $endedAt = date('Y-m-d H:i:s');
            
            $this->logger->error("Sync failed", [
                'company_prefix' => $companyPrefix,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Log failed run
            $this->targetRepo->logSyncRun(
                $companyPrefix,
                $startedAt,
                $endedAt,
                $totalFetched,
                $totalUpserted,
                $totalErrors,
                'failure'
            );
            
            return 2; // Fatal error exit code
        }
    }
}