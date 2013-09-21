<?php

require_once './autoload.php';

$account_id = 123;
$datafile   = "./gfx/{$account_id}_gfx.dat";
$gfxfile    = "./gfx/{$account_id}_gfx.png";

$flow = new PrepaidLifecycleWorkflow($account_id);

// gfx start
$rawdata = 'digraph PrepaidLifecycleWorkflow {';

// places
foreach ($flow->places AS $place => $arcs) {
    $label = str_replace("_", "\\n", $place);
    $rawdata .= "node [shape=circle,fixedsize=false,label=\"{$label}\"]; \"{$place}\";\n";
}

// transitions
foreach ($flow->transitions AS $transition => $config) {
    $label = str_replace(".", "\\n.", $transition);
    $rawdata .= "node [shape=box,fixedsize=false,label=\"{$label}\"]; \"{$transition}\";\n";

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