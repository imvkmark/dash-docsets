<?php

namespace App\Console\Commands;

use App\Models\DbWulicode;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use SplFileInfo;
use Symfony\Component\DomCrawler\Crawler;

class WulicodeCommand extends Command
{

    private static string $path = '../_wulicode/Wulicode.docset/Contents/Resources/Documents/';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wulicode {type} {--force}';

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
                $force = $this->option('force');
                if (!$force && app('files')->exists(base_path(self::$path . 'note/index.html'))) {
                    $this->warn('You need delete files manually before download it.');
                    return 0;
                }
                if (!$force) {
                    $confirm = $this->confirm('Download Need More Time, Will You Continue?', '');
                    if (!$confirm) {
                        $this->warn('Download Stopped');
                        return 0;
                    }
                }
                $startTime = Carbon::now();
                $this->downloadSite();
                $this->info('Download Success, Time : ' . Carbon::now()->diffInMinutes($startTime) . 'm');
                break;
            case 'index';
                // append style
                $this->style();
                $this->info('Style OK');

                DbWulicode::query()->delete();
                $directories = app('files')->directories(base_path(self::$path));

                foreach ($directories as $directory) {
                    $insert = collect();
                    $dir    = Str::after($directory, 'Contents/Resources/Documents/');
                    if ($dir === 'doc') {
                        $type  = 'Word';
                        $title = '笔记';
                    } else if ($dir === 'man') {
                        $type  = 'Command';
                        $title = 'Man';
                    } else {
                        $type  = 'Framework';
                        $title = 'Poppy Framework';
                    }
                    $insert->push([
                        'name' => $title,
                        'type' => $type,
                        'path' => $dir . '/index.html',
                    ]);
                    $items = app('files')->allFiles(base_path(self::$path . $dir . '/'));
                    foreach ($items as $item) {
                        /** @var SplFileInfo $item */
                        if ($item->getExtension() !== 'html') {
                            continue;
                        }
                        $path      = Str::after($item->getPathname(), 'Contents/Resources/Documents/');
                        $content   = app('files')->get($item->getPathname());
                        $crawler   = new Crawler($content);
                        $replace   = [];
                        $replaceTo = [];
                        $crawler
                            ->filterXPath('//div[@class="theme-default-content"]/h1 | //div[@class="theme-default-content"]/h2 | //div[@class="theme-default-content"]/h3')
                            ->each(function (Crawler $item) use ($path, $insert, &$replace, &$replaceTo, $content, $type) {
                                $title = Str::replace([' ', '#'], '', $item->text());
                                $id    = $item->attr('id');
                                if ($item->nodeName() === 'h1') {
                                    $insert->push([
                                        'name' => $title,
                                        'type' => $type,
                                        'path' => $path,
                                    ]);
                                } elseif ($item->nodeName() === 'h2') {
                                    $tag = "<h2 id=\"{$id}\" tabindex=\"-1\">";
                                    if (Str::contains($content, $tag . "<a name=\"//apple_ref/")) {
                                        return;
                                    }
                                    $replace[]   = $tag;
                                    $replaceTo[] = $tag . sprintf("<a name=\"//apple_ref/cpp/%s/%s\" class=\"dashAnchor\"/>", 'Section', urlencode($title));
                                } elseif ($item->nodeName() === 'h3') {
                                    $tag = "<h3 id=\"{$id}\" tabindex=\"-1\">";
                                    if (Str::contains($content, $tag . "<a name=\"//apple_ref/")) {
                                        return;
                                    }
                                    $replace[]   = $tag;
                                    $replaceTo[] = $tag . sprintf("<a name=\"//apple_ref/cpp/%s/%s\" class=\"dashAnchor\"/>", 'Section', urlencode('-' . $title));
                                }
                            });
                        $newContent = Str::replace($replace, $replaceTo, $content);
                        app('files')->replace($item->getPathname(), $newContent);
                    }

                    $this->info(count($insert->toArray()));
                    $this->info('Handle index ' . $dir . ' Success');
                    DbWulicode::query()->insert($insert->toArray());
                }
                $this->info('Index Total Success');
                break;
            case 'tar';
                chdir(base_path('../_wulicode'));
                pcntl_exec('/usr/bin/tar', [
                    '-zcvf',
                    'Wulicode.docset.tgz',
                    'Wulicode.docset'
                ]);
                break;
        }

        return 0;
    }


    private function style()
    {
        $files     = app('files')->glob(base_path(self::$path . '**/assets/css/*.css'));
        $copyright = config('app.copyright');
        foreach ($files as $filename) {
            $content = app('files')->get($filename);
            if (!Str::contains($content, $copyright)) {
                $content .= <<<CSS

{$copyright}
.navbar {
    display: none;
}

.sidebar {
    display: none;
}

.page{
    padding-left: 0;
}

CSS;
                app('files')->put($filename, $content);
            }
        }
    }


    private function downloadSite()
    {
        $urls = [
            'https://wulicode.com/note/index.html',
            'https://wulicode.com/doc/index.html',
            'https://wulicode.com/man/index.html',
        ];

        $children = [];
        foreach ($urls as $url) {
            $pid = pcntl_fork();
            if ($pid === -1) {      //进程创建失败
                $this->error('fork child process failure!');
                return;
            } else if ($pid) {      //父进程处理逻辑
                $children[] = $pid;
                pcntl_wait($status, WNOHANG);
            } else {                //子进程处理逻辑
                pcntl_exec('/usr/local/bin/wget', [
                    '--mirror',
                    '--convert-links',
                    '--adjust-extension',
                    '--page-requisites',
                    '--no-parent',
                    '--no-host-directories',
                    '--directory-prefix',
                    base_path(self::$path),
                    '--quiet',
                    '--show-progress',
                    $url
                ]);
            }
        }
        while (count($children) > 0) {
            foreach ($children as $key => $pid) {
                $res = pcntl_waitpid($pid, $status, WNOHANG);
                // -1代表error, 大于0代表子进程已退出,返回的是子进程的pid,非阻塞时0代表没取到退出子进程
                if ($res == -1 || $res > 0) {
                    unset($children[$key]);
                }
            }
        }
    }
}
