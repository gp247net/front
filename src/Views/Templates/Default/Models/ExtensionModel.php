<?php
#App\GP247\Templates\Default\Models\ExtensionModel.php
namespace App\GP247\Templates\Default\Models;

class ExtensionModel
{
    public function uninstallExtension()
    {
        return ['error' => 0, 'msg' => gp247_language_render('admin.extension.uninstall_success')];
    }

    public function installExtension()
    {
        return ['error' => 0, 'msg' => gp247_language_render('admin.extension.install_success')];
    }
    
}
