<?php
$valet_home_path = getenv('HOME') . '/.config/valet';
$valet_config = json_decode(file_get_contents("$valet_home_path/config.json"));
$tld = 'test';
$sites = [];
foreach ($valet_config->paths as $parked_path) {
    $sites = array_merge($sites, array_map(function ($site) use ($parked_path) {
        return [
            'name' => $site,
            'modified_at' => filemtime("$parked_path/$site"),
            'is_framework' => file_exists("$parked_path/$site/config.php"),
            'is_laravel' => file_exists("$parked_path/$site/.env"),
        ];
    }, array_filter(scandir($parked_path), function($site) use ($parked_path) {
        return is_dir("$parked_path/$site") 
                    && (file_exists("$parked_path/$site/.env") 
                        || file_exists("$parked_path/$site/config.php")
                        || file_exists("$parked_path/$site/public/index.html"));
    })));
}

usort($sites, fn ($a, $b) => $b['modified_at'] - $a['modified_at']);
?>
<html>
    <title>Valet Dashboard</title>
    <head>
        <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.8.2/dist/alpine.min.js" defer></script>
    </head>
    <body class="font-sans bg-gray-200 m-8">
        <div class="flex items-center justify-center" x-data="app()">
            <div class="container">
                <div class="flex justify-center mb-1">
                    <h1 class="text-xl text-gray-600">Sites in <?=$parked_path?></h1>
                </div>

                <div class="flex justify-center p-4 mb-2">
                    <div class="relative text-gray-600">
                        <input 
                            type="text" 
                            name="search" 
                            x-model="search"
                            x-on:keydown.window.prevent.slash="$refs.searchInput.focus()"
                            placeholder="/ to Search" 
                            x-ref="searchInput" 
                            class="w-96 bg-white h-10 px-5 pr-10 rounded-full text-sm focus:outline-none">
                    </div>
                </div>

                <div class="flex justify-center">
                    <div class="bg-white shadow-xl rounded-lg w-1/2">
                        <ul class="divide-y divide-gray-300">
                            <template x-for="item in filteredSites" :key="item">
                                <div 
                                    x-on:click="window.location.href = 'http://'+ item.name + '.<?=$tld?>/'"
                                    class="flex p-4 hover:bg-gray-50 cursor-pointer justify-between">
                                    <a 
                                        x-ref="valet_{{ item.name }}"
                                        x-bind:href="'http://'+ item.name + '.<?=$tld?>/'"
                                        x-text="item.name + '.<?=$tld?>'">
                                    </a>
    
                                    <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-700/10" x-show="item.is_framework">mach3framework</span>
                                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10" x-show="item.is_laravel">laravel</span>
                                </div>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <script>    
            function app() {
                return {
                    search: '',
                    siteData: Object.values(<?= json_encode($sites) ?>),
                    get filteredSites() {
                        if (this.search === "") {
                            return this.siteData;
                        }

                        return this.siteData.filter((item) => {
                            return item.name
                                .toLowerCase()
                                .includes(this.search.toLowerCase());
                        });
                    },
                }   
            }
        </script>
    </body>
</html>
