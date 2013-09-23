<?php

require_once './autoload.php';

$account_id = $_SERVER['argv'][1];

$datafile   = "./gfx/{$account_id}_gfx.dat";
$gfxfile    = "./gfx/{$account_id}_gfx.png";

$account = new Account($account_id);
$case = new WorkflowCase('PrepaidLifecycleWorkflow', $account);
$flow = $case->workflow;

// gfx start
$rawdata = 'digraph gfx {';

// places
foreach ($flow->places AS $place => $config) {
    $token = ($case->isTokenAtPlace($place)) ? ',peripheries=3' : ',peripheries=1';
    $label = str_replace("_", "\\n", $place);
    $color = ($config['description']['hasService']) ? 'green' : 'red';
    $rawdata .= "node [shape=circle,color={$color},fixedsize=false,label=\"{$label}\"{$token}]; \"{$place}\";\n";
}

// transitions
foreach ($flow->transitions AS $transition => $config) {
    $label = str_replace(".", "\\n.", $transition);
    $rawdata .= "node [shape=box,color=black,fixedsize=false,label=\"{$label}\",peripheries=1]; \"{$transition}\";\n";

    if (is_array($config['in_arcs'])) {
        foreach ($config['in_arcs'] AS $target) {

            switch ($config['trigger']) {
                case 'TIME':
                    $label = "Timer";
                    break;
                case 'MSG':
                    $message = explode('.', $config['message'])[1];
                    $label = "M:{$message}";
                    break;
                case 'AUTO':
                    $label = "Auto";
                    break;
                default:
                    $label = "";
            }

            $rawdata .= "edge [fontsize=10, label=\"{$label}\"] \"{$target}\"->\"{$transition}\"\n";
        }
    }

    if (is_array($config['out_arcs'])) {
        foreach ($config['out_arcs'] AS $target => $descriptions) {

            switch ($descriptions['type']) {
                case 'EXPLICIT_OR_SPLIT':
                    $label = "C: {$descriptions['condition']}";
                    break;
                case 'SEQ':
                    $label = "S";
                    break;
                default:
                    $label = "";
            }

            $rawdata .= "edge [fontsize=10, label=\"{$label}\"] \"{$transition}\"->\"{$target}\"\n";
        }
    }
}

// gfx end
$rawdata .= "}";

file_put_contents($datafile, $rawdata);

print_r($rawdata);

exec("dot -Tpng {$datafile} > {$gfxfile}");

echo "\n";
echo "Done! Graph file: {$gfxfile}\n";