<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Content\ContentProvider;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class Contact
{
    public function __construct(
        private readonly ContentProvider $contentProvider,
        #[Autowire('%env(DEFAULT_URI)%')]
        private readonly string $websiteUrl,
    ) {
    }

    /**
     * Returns the public professional contact channels. By design it never
     * exposes the phone number or detailed availability.
     */
    #[McpTool(name: 'get_contact_info')]
    public function getContactInfo(
        #[Schema(description: 'Purpose of the request, e.g. "recruitment".')]
        string $purpose = 'recruitment',
        #[Schema(description: 'Language (fr|en), default: fr.')]
        string $locale = 'fr',
    ): string {
        $profile = $this->contentProvider->getProfile($locale);

        $response = [
            'email' => $profile['email'],
            'linkedin_url' => $profile['linkedin'],
            'github_url' => $profile['github'],
            'website_url' => rtrim($this->websiteUrl, '/'),
            'appointment_url' => $profile['appointment'],
            'preferred_contact_method' => 'email',
        ];

        return (string) json_encode($response);
    }
}
