<?php

namespace App\Mcp;

use App\Content\ContentProvider;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;

class Profile
{
    public function __construct(
        private readonly ContentProvider $contentProvider,
    )
    {
    }

    #[McpTool(name: 'get_profile')]
    public function getProfile(
        #[Schema(description: 'Include preferences (e.g. "I prefer to work remotely").')]
        bool   $includePreferences = true,
        #[Schema(description: 'Lang (fr|en) default: fr')]
        string $lang = 'fr',
    ): string
    {
        $profile = $this->contentProvider->getProfile($lang);
        $job = $this->contentProvider->getJob($lang);

        $response = [
            'profile_id' => $profile['id'],
            'headline' => $profile['title'],
            'summary' => $profile['bio'],
            'target_roles' => $job['target_role'],
            'preferences' => $includePreferences ? $job['preferences'] : [],
        ];

        return json_encode($response);
    }
}
