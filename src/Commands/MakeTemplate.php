<?php

namespace GP247\Front\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class MakeTemplate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gp247:make-template {--name=} {--download=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make format template "php artisan gp247:make-template --name=YourTemplateName --download=0"';

    protected $tmpFolder = 'tmp';
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->option('name') ?? '';
        $download = $this->option('download') ?? 0;
        if (empty($name)) {
            echo json_encode([
                'error' => '1',
                'msg' => 'Command error'
            ]);
            exit;
        }
        $this->extension($name, $download);
    }

    //Create format extension
    protected function extension($name = '', $download = 0)
    {
        $error = 0;
        $msg = 'Success';

        $extensionKey = gp247_word_format_class($name);
        $extensionUrlKey = gp247_word_format_url($name);
        $extensionUrlKey = str_replace('-', '_', $extensionUrlKey);

        $source = "Format/template";
        $sourcePublic = "Format/template/public";
        $destination = 'GP247/Templates/'.$extensionKey;

        $sID = md5(time());
        $tmp = $this->tmpFolder."/".$sID.'/'.$extensionKey;
        $tmpPublic = $this->tmpFolder."/".$sID.'/'.$extensionKey.'/public';
        try {
            File::copyDirectory(base_path('vendor/gp247/front/src/'.$source), storage_path($tmp));
            File::copyDirectory(base_path('vendor/gp247/front/src/'.$sourcePublic), storage_path($tmpPublic));

            $appConfigJson = file_get_contents(storage_path($tmp.'/gp247.json'));
            $appConfigJson      = str_replace('Extension_Key', $extensionKey, $appConfigJson);
            $appConfigJson          = str_replace('ExtensionUrlKey', $extensionUrlKey, $appConfigJson);
            file_put_contents(storage_path($tmp.'/gp247.json'), $appConfigJson);


            $appConfig = file_get_contents(storage_path($tmp.'/AppConfig.php'));
            $appConfig      = str_replace('Extension_Key', $extensionKey, $appConfig);
            file_put_contents(storage_path($tmp.'/AppConfig.php'), $appConfig);

            $langen = file_get_contents(storage_path($tmp.'/Lang/en/lang.php'));
            $langen      = str_replace('Extension_Key', $extensionKey, $langen);
            file_put_contents(storage_path($tmp.'/Lang/en/lang.php'), $langen);

            $langvi = file_get_contents(storage_path($tmp.'/Lang/vi/lang.php'));
            $langvi      = str_replace('Extension_Key', $extensionKey, $langvi);
            file_put_contents(storage_path($tmp.'/Lang/vi/lang.php'), $langvi);

            $provider = file_get_contents(storage_path($tmp.'/Provider.php'));
            $provider      = str_replace('Extension_Key', $extensionKey, $provider);
            $provider          = str_replace('ExtensionUrlKey', $extensionUrlKey, $provider);
            file_put_contents(storage_path($tmp.'/Provider.php'), $provider);

        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            $error = 1;
        }

        try {
            if ($download) {
                $path = storage_path($this->tmpFolder.'/'.$sID.'.zip');
                gp247_zip(storage_path($this->tmpFolder."/".$sID), $path);
            } else {
                File::copyDirectory(storage_path($tmp), app_path($destination));
                File::copyDirectory(storage_path($tmpPublic), public_path($destination));
            }
            File::deleteDirectory(storage_path($this->tmpFolder.'/'.$sID));
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            $error = 1;
        }

        echo json_encode([
            'error' => $error,
            'path' => $path ?? '',
            'msg' => $msg
        ]);
    }
}
