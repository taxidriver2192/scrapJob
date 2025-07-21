<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Services\VirkdataService;

class SyncVirkdataCompany extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'virkdata:sync {company_id} {--all : Sync all companies without vat}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Virkdata info for a company and store it';

    protected $service;

    public function __construct(VirkdataService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('all')) {
            return $this->syncAllCompanies();
        }

        $companyId = $this->argument('company_id');
        $company = Company::find($companyId);

        if (! $company) {
            $this->error('Company not found.');
            return 1;
        }

        return $this->syncCompany($company);
    }

    /**
     * Sync all companies that don't have VAT data yet
     */
    protected function syncAllCompanies(): int
    {
        $companies = Company::whereNull('vat')->get();

        if ($companies->isEmpty()) {
            $this->info('No companies found that need syncing.');
            return 0;
        }

        $this->info("Found {$companies->count()} companies to sync...");

        $bar = $this->output->createProgressBar($companies->count());
        $bar->start();

        $success = 0;
        $errors = 0;
        $skipped = 0;

        foreach ($companies as $company) {
            // Skip companies with existing errors
            if (!is_null($company->error)) {
                $skipped++;
            } else {
                $result = $this->syncCompany($company, false);
                if ($result === 0) {
                    $success++;
                } else {
                    $errors++;
                }
                // Add small delay to be nice to the API
                sleep(1);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Build summary message
        $summary = "Sync completed. Success: {$success}, Errors: {$errors}";
        if ($skipped > 0) {
            $summary .= ", Skipped (existing errors): {$skipped}";
        }
        $this->info($summary);

        return 0;
    }

    /**
     * Sync a single company
     */
    protected function syncCompany(Company $company, bool $verbose = true): int
    {
        if ($verbose) {
            $this->info("Fetching Virkdata for \"{$company->name}\"...");
        }

        $result = $this->service->fetch($company->name);

        if (isset($result['error'])) {
            $company->error = $result['error'];
            $company->save();

            if ($verbose) {
                $this->error('Error from Virkdata, stored in `error` field.');
                $this->line('Error details: ' . json_encode($result['error']));
            }
            return 1;
        }

        // Remove name so we don't overwrite it
        $originalName = $company->name;
        unset($result['name']);

        // Prepare data for storage
        $updateData = $this->prepareVirkdataForStorage($result);

        $company->fill($updateData);
        $company->error = null;
        $company->save();

        if ($verbose) {
            $this->info('Company data updated successfully.');
            $this->table(['Field', 'Value'], [
                ['VAT', $company->vat],
                ['Status', $company->status],
                ['Address', $company->address],
                ['City', $company->city],
                ['Phone', $company->phone],
                ['Website', $company->website],
                ['Employees', $company->employees],
            ]);
        }

        return 0;
    }

    /**
     * Prepare Virkdata response for storage
     */
    protected function prepareVirkdataForStorage(array $data): array
    {
        // Ensure JSON fields are properly encoded if they're arrays
        if (isset($data['owners']) && is_array($data['owners'])) {
            $data['owners'] = json_encode($data['owners']);
        }

        if (isset($data['financial_summary']) && is_array($data['financial_summary'])) {
            $data['financial_summary'] = json_encode($data['financial_summary']);
        }

        return $data;
    }
}
