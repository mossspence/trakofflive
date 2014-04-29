<?php

namespace moss\musicapp\finder;

/**
 * Description of bpmTools
 *
 * @author oneilstuart
 */
class bpmTools
{

    /**
     * getLocalBPMs
     * 
     * @param integer $bpm
     * @return integer array 
     * 
     * Calculate the bpm range within established paramaters and return array of 2 values (upper and lower)
     */
    public static $standardRange = array('upper' => 180, 'lower' => 50);
    
    public static function isStandardBPM($bpm)
    {
        return (isset($bpm) && !empty($bpm) && (is_numeric($bpm)) && abs($bpm) < self::$standardRange['upper'] && abs($bpm) > self::$standardRange['lower']);
    }
    public static function getLocalBPMs($bpm)
    {
        $percentage = 4;    // Exceeding this percentage delta of BPM will change the key of the song
        // It's actually 6%, so I'm playing within established paramaters.
        $bpm_range = self::$standardRange;  //  array('lower' => NULL, 'upper' => NULL);

        // assert given BPM is valid
        if (self::isStandardBPM($bpm)) {
            $bpm_range['upper'] = round((1 + ($percentage / 100)) * abs((intval(floor($bpm)))));
            $bpm_range['lower'] = round((1 - ($percentage / 100)) * abs((intval(floor($bpm)))));
        }
        return $bpm_range;
    }
    
    public static function getDoubleBPM($bpm)
    {
        return ((self::isStandardBPM($bpm)) && $bpm * 2 < self::$standardRange['upper'])?$bpm * 2:NULL;
    }

    public static function getHalfBPM($bpm)
    {
        return ((self::isStandardBPM($bpm)) && (round($bpm / 2)) > self::$standardRange['lower'])?round($bpm / 2):NULL;
    }
}
//  test BPMs
/*
$top = 14;
echo '<table border=1 padding=3 cellspacing=3><tr><th>BPM</th><th>lower</th><th>upper</th>';
echo '<th>Lower Doubled</th><th>Upper Doubled</th><th>Lower Halved</th><th>Upper Halved</th></tr>';
for ($i = -128; $i <= $top; $i=$i+$top)
{
    $bpm = $i;
    $array = bpmTools::getLocalBPMs($bpm);
    $upper = $array['upper'];
    $lower = $array['lower'];
    
    // Calculate double and half BPMs in case of tagging/analysis mistakes and/or find new creative songs
    
    $u2 = bpmTools::getDoubleBPM($upper);
    $l2 = bpmTools::getDoubleBPM($lower);
    $uhalf = bpmTools::getHalfBPM($upper);
    $lhalf = bpmTools::getHalfBPM($lower);
    
    $bgcolor = ($i % 2) ? '#fff' : '#efefef';
    echo "<tr style=\"background-color: $bgcolor\"><td>$i</td><td>$lower</td><td>$upper</td>";
    echo "<td>$l2</td><td>$u2</td><td>$lhalf</td><td>$uhalf</td></tr>";
}
echo '</table>';
*/

?>
