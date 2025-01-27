<?php
#App\GP247\Templates\Default\Models\ExtensionModel.php
namespace App\GP247\Templates\Default\Models;

class ExtensionModel
{
    public function uninstallExtension()
    {
        return ['error' => 0, 'msg' => 'uninstall success'];
    }

    public function installExtension()
    {
        return ['error' => 0, 'msg' => 'install success'];
    }
    
}
