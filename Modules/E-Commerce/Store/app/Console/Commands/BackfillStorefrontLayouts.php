<?php

namespace Modules\Ecommerce\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Ecommerce\Models\StorefrontLayout;
use Modules\Ecommerce\Support\EcommerceClientContext;

class BackfillStorefrontLayouts extends Command
{
    protected $signature = 'ecommerce:backfill-storefronts {--dry-run : Report changes without writing them}';

    protected $description = 'Provision the current storefront layout and URL slug for existing active clients without replacing custom layouts.';

    public function handle(EcommerceClientContext $clientContext): int
    {
        $schema = Schema::connection('ecommerce');

        if (! $schema->hasTable('storefront_layouts')) {
            $this->error('The ecommerce storefront_layouts table does not exist. Run ecommerce:install-schema first.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $slugUpdates = 0;
        $layoutCreates = 0;

        Company::query()
            ->where('status', 'Active')
            ->orderBy('id')
            ->each(function (Company $company) use ($clientContext, $dryRun, &$slugUpdates, &$layoutCreates): void {
                if (blank($company->ecommerce_slug)) {
                    $slug = $this->uniqueSlug($company->company_name, $company->id);
                    $this->line("{$company->company_name}: storefront slug {$slug}");
                    $slugUpdates++;

                    if (! $dryRun) {
                        $company->update(['ecommerce_slug' => $slug]);
                        $company->refresh();
                    }
                }

                $clientContext->setClientId((int) $company->id);
                $existingLayout = StorefrontLayout::query()->first();

                if ($existingLayout) {
                    return;
                }

                $this->line("{$company->company_name}: new editable storefront layout");
                $layoutCreates++;

                if (! $dryRun) {
                    $layout = StorefrontLayout::defaultFor($company);
                    StorefrontLayout::create([
                        'draft_layout' => $layout,
                        'published_layout' => $layout,
                    ]);
                }
            });

        $this->info(sprintf(
            '%s: %d storefront slug(s), %d layout(s) %s.',
            $dryRun ? 'No changes made' : 'Storefronts ready',
            $slugUpdates,
            $layoutCreates,
            $dryRun ? 'would be created' : 'created'
        ));

        return self::SUCCESS;
    }

    private function uniqueSlug(string $companyName, int $companyId): string
    {
        $base = Str::slug($companyName) ?: 'store';
        $candidate = $base;
        $suffix = 2;

        while (Company::query()->where('ecommerce_slug', $candidate)->whereKeyNot($companyId)->exists()) {
            $candidate = $base.'-'.$suffix++;
        }

        return $candidate;
    }
}
