<?php

namespace App\Console\Commands;

use App\Models\ProfessionalCertification;
use Illuminate\Console\Command;

/**
 * Approve or reject a Passport certificate after checking the uploaded file.
 * With no id, lists the certificates still waiting for review.
 */
class ReviewCertification extends Command
{
    protected $signature = 'passport:review-certification {id? : Certification id} {decision? : verified or rejected}';

    protected $description = 'List pending Passport certificates, or mark one verified/rejected';

    public function handle(): int
    {
        $id = $this->argument('id');

        if (! $id) {
            $pending = ProfessionalCertification::with('professional.user')->where('status', 'pending')->oldest()->get();
            $this->table(
                ['ID', 'Professional', 'Title', 'Issuer', 'Issued', 'File'],
                $pending->map(fn ($c) => [
                    $c->id,
                    $c->professional?->user?->name,
                    $c->title,
                    $c->issuer,
                    $c->issued_on?->toDateString(),
                    $c->file_url ? url($c->file_url) : '-',
                ])
            );

            return self::SUCCESS;
        }

        $decision = $this->argument('decision');
        if (! in_array($decision, ['verified', 'rejected'], true)) {
            $this->error('Decision must be "verified" or "rejected".');

            return self::INVALID;
        }

        $cert = ProfessionalCertification::findOrFail($id);
        $cert->update(['status' => $decision, 'reviewed_at' => now()]);
        $this->info("Certification #{$cert->id} \"{$cert->title}\" marked {$decision}.");

        return self::SUCCESS;
    }
}
