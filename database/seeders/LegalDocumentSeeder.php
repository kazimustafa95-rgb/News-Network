<?php

namespace Database\Seeders;

use App\Models\LegalDocument;
use Illuminate\Database\Seeder;

class LegalDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $documents = [
            [
                'slug' => 'terms-and-conditions',
                'title' => 'Terms and Conditions',
                'summary' => 'These terms explain how Community Will may be used, what content standards apply, and what responsibilities users accept when accessing the platform.',
                'content' => <<<'HTML'
<h2>Acceptance of Terms</h2>
<p>By accessing or using Community Will, you agree to follow these Terms and Conditions and all applicable laws and regulations.</p>
<h2>Use of the Platform</h2>
<p>Community Will provides hyper-local news, archived content, subscription features, and community submissions. You agree to use the platform only for lawful purposes.</p>
<h2>User Content and Submissions</h2>
<p>If you submit news, images, or video, you confirm that you own the rights to that content or are authorized to share it. We may review, approve, reject, edit, or remove submissions in line with our editorial standards.</p>
<h2>Subscriptions and Purchases</h2>
<p>Paid features, including archived content access and subscription-based submission privileges, are governed by the purchase terms presented at checkout.</p>
<h2>Prohibited Conduct</h2>
<p>You must not upload unlawful, abusive, defamatory, misleading, infringing, or harmful content, or attempt to interfere with platform security or service availability.</p>
<h2>Contact</h2>
<p>If you have questions about these terms, contact the Community Will support team.</p>
HTML,
            ],
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'summary' => 'This policy explains what information Community Will collects, how it is used, and how location, account, and subscription data are handled.',
                'content' => <<<'HTML'
<h2>Information We Collect</h2>
<p>We may collect account details, profile information, location preferences, device information, purchase history, and content that you submit through the platform.</p>
<h2>How We Use Information</h2>
<p>We use collected information to deliver county-based news feeds, manage subscriptions, support archived content purchases, review submissions, send notifications, and improve service quality.</p>
<h2>Location Data</h2>
<p>Location access is used to personalize your experience and help surface local news relevant to your selected county or region.</p>
<h2>Content Moderation and Review</h2>
<p>Submitted content may be reviewed by administrators for quality, safety, and editorial compliance before publication.</p>
<h2>Data Sharing</h2>
<p>We do not sell your personal information. We may share limited information with payment, hosting, analytics, or notification providers as needed to operate the platform.</p>
<h2>Your Choices</h2>
<p>You may update your profile information, manage certain notification settings, and contact us about privacy-related requests.</p>
HTML,
            ],
        ];

        foreach ($documents as $document) {
            LegalDocument::query()->updateOrCreate(
                ['slug' => $document['slug']],
                [
                    'title' => $document['title'],
                    'summary' => $document['summary'],
                    'content' => $document['content'],
                    'is_published' => true,
                ],
            );
        }
    }
}
