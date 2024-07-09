<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\BlacklistUrl;
use App\Models\Extractor;
use Illuminate\Support\Facades\Cache;
use PureDevLabs\DMCA;

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
        $sitesData = Cache::rememberForever('extractors:all', function() {
            return json_encode(Extractor::all());
        });
        $sitesData = json_decode($sitesData, true);
        $sitesData = (json_last_error() == JSON_ERROR_NONE) ? collect($sitesData) : [];
        if (empty($sitesData)) dd('Error: No sites data!');
        $this->sites = $sitesData->pluck('formal_name', 'name');
        $this->siteId = $sitesData->where('name', $this->site)->first()['id'];
        $this->blockedUrls = $this->existingUrls = BlacklistUrl::where('extractor_id', $this->siteId)->pluck('url')->join("\n");
    }

    public function change()
    {
		//dd($this->site);
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
        $saved = false;
        $urls = trim(preg_replace('/\|+/', '\n', preg_replace('/\s/', '|', $this->blockedUrls)));
        //dd($urls . "<br>" . $this->existingUrls);
        $newUrls = trim(preg_replace('/' . preg_quote($this->existingUrls, '/') . '/', "", $urls, 1));
        //dd($newUrls);
        if (!empty($newUrls) || (empty($urls) && !empty($this->existingUrls)))
        {
            if ($newUrls == $urls)
            {
                BlacklistUrl::where('extractor_id', $this->siteId)->delete();
                $saved = true;
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
                    //dd($data);
                    BlacklistUrl::insert($data);
                    $saved = true;
                }
            }
        }
        if ($saved)
        {
            $this->existingUrls = $urls;
            $cachedUrls = DMCA::ConvertUrlsToJson($urls);
            $cachedIds = DMCA::ConvertUrlsToJson($urls, true);
            //dd($cachedUrls);
            Cache::put('blacklist:' . $this->site . ':urls', $cachedUrls);
            Cache::put('blacklist:' . $this->site . ':ids', $cachedIds);
            $this->emit('saved');
        }
    }

    public function render()
    {
        return view('livewire.url-blacklist');
    }
}
