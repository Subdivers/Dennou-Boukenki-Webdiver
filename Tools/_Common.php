<?php

namespace {
    require_once __DIR__ . "/../vendor/autoload.php";
}

namespace ASS {
    function DecodeTimestamp($s) {
        sscanf($s, "%d:%d:%d.%d", $v1, $v2, $v3, $v4);
        return $v1 * 3600000 + $v2 * 60000 + $v3 * 1000 + $v4 * 10;
    }
    
    function EncodeTimestamp(string $t) {
        return sprintf("%01d:%02d:%02d.%02d", intval($t / 3600 / 1000), (intval($t / 60000) % 60), (intval($t / 1000) % 60), ($t % 1000) / 10);
    }
    
    function Parse(string $data) {
        preg_match_all('/^(?<linetype>Comment|Dialogue):\s*(?<layer>[^,]*?)\s*,\s*(?<start>[^,]*?)\s*,\s*(?<end>[^,]*?)\s*,\s*(?<style>[^,]*?)\s*,\s*(?<name>[^,]*?)\s*,\s*(?<marginl>[^,]*?)\s*,\s*(?<marginr>[^,]*?)\s*,\s*(?<marginv>[^,]*?)\s*,\s*(?<effect>[^,]*?)\s*,\s*(?<text>.*?)\s*$/uim', $data, $res, PREG_SET_ORDER);
        foreach ($res as &$line) {
            $line['start'] = timestamp_decode($line['start']);
            $line['end'] = timestamp_decode($line['end']);
            $line['length'] = $line['end'] - $line['start'];
        }
        return $res;
    }
    
    class AssFile {
        private $script_info = [];
        private $aegisub_garbage = [];
        private $styles = [];
        private $styles_format;
        private $events = [];
        private $events_format;
        
        public function __construct(string $data) {
            $lines = explode("\n", $data);
            $target = []; // dummy
            foreach($lines as $line) {
                $line = trim($line);
                if ($line === "") continue;
                switch($line) {
                    case "[Script Info]":
                        $target = &$this->script_info;
                        break;
                    case "[Aegisub Project Garbage]":
                        $target = &$this->aegisub_garbage;
                        break;
                    case "[V4+ Styles]":
                        $target = &$this->styles;
                        break;
                    case "[Events]":
                        $target = &$this->events;
                        break;
                    default:
                        $target[] = $line;
                }
            }
            
        }
    }
}