<?php

namespace Axn\ModelsScanner\Controllers;

use Axn\ModelsScanner\Services\ScanMerger;
use Illuminate\View\View;

class ScanController
{
    public function __invoke(ScanMerger $scanMerger): View
    {
        return view('models-scanner::scan', [
            'mergedInfos' => $scanMerger->execute(),
        ]);
    }
}
