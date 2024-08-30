<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\BlacklistUrl;
use App\Models\Extractor;
use Illuminate\Support\Facades\Cache;

class UrlBlacklist extends Component
{
    public $blockedUrls;
    public $existingUrls;
    public $sites;
    public $site = "youtube";
    public $siteId;
    protected $listeners = ['refreshBlacklistUrls'];

    public function mount()
    {
        $extractors = Extractor::all();
        $sitesData = (!$extractors->isEmpty()) ? collect($extractors) : [];
        $this->sites = $sitesData->pluck('formal_name', 'name');
        $this->siteId = $sitesData->where('name', $this->site)->first()->id;
        $this->blockedUrls = $this->existingUrls = BlacklistUrl::where('extractor_id', $this->siteId)->pluck('url')->join("\n");
    }

    public function read()
    {
        return BlacklistUrl::with('extractor')->get();
    }

    public function change()
    {
		$this->emit('refreshBlacklistUrls', $this->site);
    }

    public function refreshBlacklistUrls($site)
    {
        $this->site = $site;
        $this->mount();
    }

    public function updateBlacklistURLs()
    {
        $data = [];
        $urls = trim(preg_replace('/\|+/', '\n', preg_replace('/\s/', '|', $this->blockedUrls)));
        $newUrls = trim(preg_replace('/' . preg_quote($this->existingUrls, '/') . '/', "", $urls, 1));
        if (!empty($newUrls) || (empty($urls) && !empty($this->existingUrls)))
        {
            if ($newUrls == $urls)
            {
                BlacklistUrl::where('extractor_id', $this->siteId)->delete();
            }
            if (!empty($newUrls))
            {
                $newUrlsArr = preg_split('/\\\n/', $newUrls, -1, PREG_SPLIT_NO_EMPTY);
                if (!empty($newUrlsArr))
                {
                    foreach ($newUrlsArr as $newUrl)
                    {
                        $data[] = ['extractor_id' => $this->siteId, 'url' => $newUrl, 'created_at' => now(), 'updated_at' => now()];
                    }
                    BlacklistUrl::insert($data);
                    Cache::store('permaCache')->forget('blockedurls');
                    $this->emit('saved');
                }
            }
        }
    }

    public function render()
    {
        return view('livewire.url-blacklist');
    }
}
