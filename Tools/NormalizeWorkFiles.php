#!/usr/bin/php
<?php
require_once __DIR__ . "/_Common.php";

const SOURCES = [
    "/Episodes",
];

class Worker {
    const TEMPLATE = <<<'ASS'
[Script Info]
ScriptType: v4.00+
WrapStyle: 0
ScaledBorderAndShadow: yes
PlayResX: 640
PlayResY: 480
YCbCr Matrix: TV.601
Video Aspect Ratio: c1.33333

[Aegisub Project Garbage]
Last Style Storage: Default
Audio File: ../Videos/Dennou Boukenki Webdiver - Episode ${FILENAME}.mp4
Video File: ../Videos/Dennou Boukenki Webdiver - Episode ${FILENAME}.mp4
Video AR Value: 1.333333
Video Zoom Percent: 1.000000

[V4+ Styles]
${STYLES}

[Events]
${EVENTS}
ASS;
    
    /** @var \ChaosTangent\ASS\Script */
    private $data;
    
    private $filename;
    
    /**
     * Worker constructor.
     *
     * @param string $filename
     *
     * @throws \ChaosTangent\ASS\Exception\InvalidScriptException
     */
    public function __construct(string $filename) {
        $reader = new ChaosTangent\ASS\Reader();
        $this->filename = $filename;
        $this->data = $reader->fromFile($filename);
    }
    
    private function _filter_fx(ChaosTangent\ASS\Line\Line $line) {
    	if (!($line instanceof \ChaosTangent\ASS\Line\Dialogue))
    		return true;
    	return $line->getEffect() !== "fx";
    }
    
    private function _filter_furigana(ChaosTangent\ASS\Line\Line $line) {
        if (!($line instanceof \ChaosTangent\ASS\Line\Style))
            return true;
        return substr($line->getName(),-9) !== "-furigana";
    }
    
    private function _map_lines(ChaosTangent\ASS\Line\Line $line) {
        return trim("{$line->getKey()}: {$line->getValue()}");
    }
    
    public function process() {
        $styles = $this->data->getBlock("V4+ Styles")->getLines();
        $events = $this->data->getBlock("Events")->getLines();
    
        $styles = array_filter($styles, [$this, "_filter_furigana"]);
        
        $events = array_filter($events, [$this, "_filter_fx"]);
        
        $result = str_replace([
            '${FILENAME}',
            '${STYLES}',
            '${EVENTS}',
            "\r\n",
        ], [
            explode(".", basename($this->filename))[0],
            implode("\n", array_map([$this, "_map_lines"], $styles)),
            implode("\n", array_map([$this, "_map_lines"], $events)),
            "\n",
        ], self::TEMPLATE);
        
        $result = trim($result) . "\n";
        
        return $result;
    }
}


foreach (SOURCES as $directory) {
    $directory = __DIR__ . "/../$directory/";
    if (!(($dh = opendir($directory))))
        continue;
    
    while (false !== ($fn = readdir($dh))) {
        if (strtolower(substr($fn, -4)) !== ".ass")
            continue;
        
        try {
            $data = new Worker($directory . $fn);
            $data = $data->process();
        } catch (Throwable $t) {
            echo "Error occurred with the file $directory/$fn: {$t->getMessage()}\n";
            exit(-1);
        }
        file_put_contents($directory . $fn, $data);
    }
    
    closedir($dh);
}