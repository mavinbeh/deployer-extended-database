<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-backup
 */
task('db:backup', function () {
    $verbosity = (new ConsoleUtility())->getVerbosityAsParameter(output());
    if (!(new ConsoleUtility())->getOption('dumpcode')) {
        $dumpCode = (new ConsoleUtility())->getOption('dumpcode');
    } else {
        if (get('current_stage') == get('target_stage')) {
            $list = [];
            if (testLocally('[ -e {{deploy_path}}/releases ]')) {
                $list = explode("\n", runLocally('cd releases && ls -t -1 -d */'));
                $list = array_map(function ($release) {
                    return basename(rtrim(trim($release), '/'));
                }, $list);
            }
        } else {
            $list = get('releases_list');
        }
        $list = array_filter($list, function ($release) {
            return preg_match('/^[\d\.]+$/', $release);
        });
        $dumpCodeRealese = '';
        if (count($list) > 0) {
            $currentRelease = (int)max($list);
            $dumpCodeRealese = '_for_release_' . $currentRelease;
        }
        $dumpCode = 'backup' . $dumpCodeRealese . '_' . md5(microtime(true) . rand(0, 10000));
    }
    $options = (new ConsoleUtility())->getOptionsForCliUsage(['dumpcode' => $dumpCode]);
    if (get('current_stage') == get('target_stage')) {
        runLocally('{{local/bin/deployer}} db:export ' . $options . ' ' . $verbosity);
        runLocally('{{local/bin/deployer}} db:compress ' . $options . ' ' . $verbosity);
        runLocally('{{local/bin/deployer}} db:dumpclean' . $verbosity);
    } else {
        $activePath = get('deploy_path') . '/' . (test('[ -L {{deploy_path}}/release ]') ? 'release' : 'current');
        run('cd ' . $activePath . ' && {{bin/php}} {{bin/deployer}} db:export ' . $options . ' ' . $verbosity);
        run('cd ' . $activePath . ' && {{bin/php}} {{bin/deployer}} db:compress ' . $options . ' ' . $verbosity);
        run('cd ' . $activePath . ' && {{bin/php}} {{bin/deployer}} db:dumpclean' . $verbosity);
    }
})->desc('Do backup of database (export and compress)');