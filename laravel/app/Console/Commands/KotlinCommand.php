<?php

namespace App\Console\Commands;

use App\Models\DbKotlin;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Finder\SplFileInfo;

class KotlinCommand extends Command
{
    private static string $path = '../_kotlin/Kotlin.docset/Contents/Resources/Documents/';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kotlin {type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    private Collection $allInsert;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $type = $this->argument('type');
        switch ($type) {
            case 'download':
                if (app('files')->exists(base_path(self::$path . 'api/latest/jvm/stdlib/index.html'))) {
                    $this->warn('You need delete files manually before download it.');
                    return 0;
                }
                $confirm = $this->confirm('Download Need More Time, Will You Continue?', '');
                if (strtolower($confirm) !== 'y') {
                    $this->warn('Download Stopped');
                    return 0;
                }
                $startTime = Carbon::now();
                $this->downloadSite();
                $this->info('Download Success, Time : ' . Carbon::now($startTime)->diffInMinutes() . 'm');
                break;
            case 'index':

                $this->style();

                if (!app('files')->exists(base_path(self::$path . 'api/latest/jvm/stdlib/index.html'))) {
                    $this->warn('You need run download first');
                    return 0;
                }

                $files           = app('files')->allFiles(base_path(self::$path));
                $this->allInsert = new Collection();
                $startTime       = Carbon::now();
                $trimPath        = 'api/latest/jvm/stdlib/';
                DbKotlin::query()->delete();
                foreach ($files as $file) {
                    /** $file */
                    /** @var SplFileInfo $file */
                    if ($file->getExtension() !== 'html') {
                        continue;
                    }
                    $lastCount = $this->allInsert->count();
                    $this->parse($file);

                    $diff         = Carbon::now()->diffInSeconds($startTime);
                    $currentCount = $this->allInsert->count();
                    if ($lastCount !== $currentCount) {
                        $this->info('Count : ' . $this->allInsert->count() . ', Use time :' . $diff . 's, Path: ' . Str::replace($trimPath, '', $file->getRelativePathname()));
                    }
                }
                //        DbKotlin::query()->insert($this->allInsert->toArray());
                $this->info('Gen Db Success, Total : ' . $this->allInsert->count(), ', Use ' . Carbon::now()->diffInSeconds($startTime) . 's.');
                break;
            case 'tar';
                chdir(base_path('../_kotlin'));
                pcntl_exec('/usr/bin/tar', [
                    '-zcvf',
                    'Kotlin.docset.tgz',
                    'Kotlin.docset'
                ]);
                break;
        }
        return 0;
    }

    /**
     * @param SplFileInfo $file
     */
    private function parse(SplFileInfo $file)
    {
        $content = $file->getContents();
        $crawler = new Crawler($content);
        $insert  = collect();
        $crawler
            ->filterXPath('//div[@class="node-page-main"] | //div[@class="overload-group"]')
            ->each(function (Crawler $node) use ($crawler, $file, $insert) {
                $node->filterXPath('//div[@class="signature"]')->each(function (Crawler $node) use ($crawler, $file, $insert) {
                    $codeType = '';
                    if (!Str::contains($node->text(), ['public', 'private', 'protected', 'open', 'const', 'abstract', 'suspend', 'operator'])) {
                        // calc code type
                        if (Str::contains($node->text(), ['class', 'typealias'])) {
                            $codeType = 'Class';
                        } elseif (Str::contains($node->text(), ['interface'])) {
                            $codeType = 'Interface';
                        } elseif (Str::contains($node->text(), ['fun'])) {
                            $codeType = 'Function';
                        } elseif (Str::contains($node->text(), ['val', 'var'])) {
                            $codeType = 'Property';
                        } elseif (Str::contains($node->text(), ['object'])) {
                            $codeType = 'Object';
                        } elseif (Str::contains($node->text(), ['<init>'])) {
                            $codeType = 'Constructor';
                        } elseif (preg_match('/[a-zA-Z0-9]*\(.*\)/', $node->text()) || preg_match('/[a-zA-Z0-9]*\(.*\)/', $node->text())) {
                            $codeType = 'Constructor';
                        } elseif (preg_match('/[A-Z0-9\_]+/', $node->text())) {
                            $codeType = 'Enum';
                        }
                    }
                    if ($codeType) {
                        $text = $crawler->filter('div.api-docs-breadcrumbs')->text();
                        if (Str::contains($text, '/')) {
                            $arrText = array_map('trim', explode('/', $text));
                            $expect  = array_slice($arrText, 2);
                            if (count($expect)) {
                                $name = implode('.', $expect);
                                //    $this->info(sprintf('%s -> %s -> %s', $name, $codeType, $file->getRelativePathname()));
                                $insert->push([
                                    'name' => $name,
                                    'type' => $codeType,
                                    'path' => $file->getRelativePathname(),
                                ]);
                            }
                        }
                    }
                });
            });
        if ($insert->count()) {
            DbKotlin::query()->insert($insert->toArray());
        }
        if ($insert->count()) {
            $this->allInsert->push($insert->toArray());
        }
    }


    private function downloadSite()
    {
        // wget --mirror --convert-links --adjust-extension --page-requisites --no-parent --no-host-directories --directory-prefix="kotlin" --progress="dot" "https://kotlinlang.org/api/latest/jvm/stdlib/index.html"
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
            'https://kotlinlang.org/api/latest/jvm/stdlib/index.html'
        ]);
    }


    private function style()
    {
        $files     = app('files')->files(base_path(self::$path . '_/assets/'));
        $copyright = config('app.copyright');
        foreach ($files as $file) {
            if (Str::contains($file->getBaseName(), 'styles.css')) {
                $filename = $file->getPathName();
                $content  = app('files')->get($filename);
                if (!Str::contains($content, $copyright)) {
                    $content .= <<<CSS

{$copyright}
/* header & nav */
header {
    display: none !important;
}

.docs-nav,
.docs-nav-new {
    display: none;
}

/* side */
.g-3 {
    display: none;
}

/* content */
.page-content {
    width: 100%;
}

.g-layout {
    width: 85%;
    padding: 40px;
}
CSS;
                    app('files')->put($filename, $content);
                }
            }
        }
    }
}
