<?php

namespace App\Console\Commands;

use App\Models\PhpAim;
use App\Models\PhpOrigin;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class PhpDocsetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'php:docset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // append style
        $this->style();

        $items = PhpOrigin::query()->get();
        PhpAim::query()->delete();

        $sql = [];
        foreach ($items as $item) {
            $filename = Str::replace('www.php.net/manual/en/', '', $item->path);
            $name     = $item['name'];
            $type     = $item['type'];
            $path     = $filename;
            if (strtolower($item->type) === 'guide') {
                $content = file_get_contents(base_path('../_php/PHP.docset/Contents/Resources/Documents/' . $filename));
                if (preg_match('/<(title)>(.*)<\/\1>/', $content, $match)) {
                    $name = $match[2];
                }
            }
            $sql[] = compact('name', 'type', 'path');

        }
        PhpAim::query()->insert($sql);
        return 0;
    }


    private function style()
    {
        $files = app('files')->files(base_path('../_php/PHP.docset/Contents/Resources/Documents/styles'));

        foreach ($files as $file) {
            if (Str::contains($file->getBaseName(), 'medium.css')) {
                $filename = $file->getPathName();
                $content  = app('files')->get($filename);
                if (!Str::contains($content, '/*---- Append By Duoli(https://github.com/imvkmark) ----*/')) {
                    $content .= <<<CSS

/*---- Append By Duoli(https://github.com/imvkmark) ----*/
#layout-content {
    background: transparent;
}

.navbar {
    display: none;
}

body {
    margin-top: 0;
}
html{
    background: transparent;
}
CSS;
                    app('files')->put($filename, $content);
                }
            }
        }
    }
}
