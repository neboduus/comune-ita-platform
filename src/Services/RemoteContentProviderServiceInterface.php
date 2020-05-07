<?php

namespace App\Services;

use App\Entity\RemoteContent;

interface RemoteContentProviderServiceInterface
{
    /**
     * @param array $enti
     *
     * @return RemoteContent[]
     */
    public function getLatestNews(array $enti);

    /**
     * @param array $enti
     *
     * @return RemoteContent[]
     */
    public function getLatestDeadlines(array $enti);
}
