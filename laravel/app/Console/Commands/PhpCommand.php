<?php

namespace App\Console\Commands;

use App\Models\DbPhpAim;
use App\Models\DbPhpOrigin;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class PhpCommand extends Command
{

    private static string $path = '../_php/Php.Cn.docset/Contents/Resources/Documents/';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'php {type}';

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
        $type = $this->argument('type');
        switch ($type) {
            case 'download';
                if (app('files')->exists(base_path(self::$path . 'index.html'))) {
                    $this->warn('You need delete files manually before download it.');
                    return 0;
                }
                $confirm = $this->confirm('Download Need More Time, Will You Continue?');
                if (!$confirm) {
                    $this->warn('Download Stopped');
                    return 0;
                }
                $startTime = Carbon::now();
                $this->downloadSite();
                $this->info('Download Success, Time : ' . Carbon::now()->diffInSeconds($startTime) . 's');
                if (!app('files')->exists(base_path(self::$path))) {
                    app('files')->makeDirectory(base_path(self::$path), 0700, true);
                }

                pcntl_exec('/usr/bin/tar', [
                    '-zxvf',
                    dirname(base_path(self::$path)) . '/php.cn.tar.gz',
                    '--strip-components',
                    '1',
                    '--directory',
                    base_path(self::$path)
                ]);
                $this->info('Extract Success, Time : ' . Carbon::now()->diffInSeconds($startTime) . 's');
                break;
            case 'index';
                // append style
                $this->style();

                $items = DbPhpOrigin::query()->get();
                DbPhpAim::query()->delete();

                $sql = [];
                foreach ($items as $item) {
                    $filename = Str::replace('www.php.net/manual/en/', '', $item->path);
                    $name     = $item['name'];
                    $type     = $item['type'];
                    $path     = $filename;
                    if (strtolower($item->type) === 'guide') {
                        $content = file_get_contents(base_path(self::$path . $filename));
                        if (preg_match('/<(title)>(.*)<\/\1>/', $content, $match)) {
                            $name = $match[2];
                        }
                    }
                    $sql[] = compact('name', 'type', 'path');

                }
                DbPhpAim::query()->insert($sql);
                $this->info('Index Success');
                break;
            case 'tar';
                pcntl_exec('/usr/bin/tar', [
                    '-zcvf',
                    base_path('../_php') . '/Php.Cn.docset.tgz',
                    base_path(self::$path)
                ]);
                break;
        }

        return 0;
    }


    private function style()
    {
        $files     = app('files')->files(base_path(self::$path . 'styles'));
        $copyright = config('app.copyright');
        foreach ($files as $file) {
            if (Str::contains($file->getBaseName(), 'medium.css')) {
                $filename = $file->getPathName();
                $content  = app('files')->get($filename);
                if (!Str::contains($content, $copyright)) {
                    $content .= <<<CSS

{$copyright}
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


    private function downloadSite()
    {
        $url = 'https://www.php.net/distributions/manual/php_manual_zh.tar.gz';
        pcntl_exec('/usr/local/bin/wget', [
            '--no-parent',
            '--no-host-directories',
            '--directory-prefix',
            base_path(self::$path . '../'),
            '--output-document',
            base_path(self::$path . '../php.cn.tar.gz'),
            '--quiet',
            '--show-progress',
            $url
        ]);
    }
}
