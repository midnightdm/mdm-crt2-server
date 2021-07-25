<?php
if(php_sapi_name() !='cli') { exit('No direct script access allowed.');}

/* * * * * * * * * * * * * * * * * * * 
 *  daemon2/classes/MyAIS.class.php
 * 
 *  Uses the parent AIS class to decode raw NMEA data 
 *  supplied by the AISMon app via UDP port or text file.
 *  This custom implementation accepts a callback to the
 *  PlotDaemon object into which the decoded data is stored.
 */
class MyAIS extends AIS {
    public $plotDaemon;

    public function __construct($callBack=null) {
        $this->plotDaemon = $callBack;
    }
	// This function is Overridable and is called by process_ais_itu(...) method
	function decode_ais($_aisdata, $_aux) {
		$ro = new stdClass(); // return object
		$ro->cls = 0; // AIS class undefined, also indicate unparsed msg
		$ro->name = '';
		$ro->sog = -1.0;
		$ro->cog = 0.0;
		$ro->lon = 0.0;
		$ro->lat = 0.0;
		$ro->ts = time();
		$ro->id = bindec(substr($_aisdata,0,6));
		$ro->mmsi = bindec(substr($_aisdata,8,30));
		
		if ($ro->id >= 1 && $ro->id <= 3) {
			$ro->cog = bindec(substr($_aisdata,116,12))/10;
			$ro->sog = bindec(substr($_aisdata,50,10))/10;
			$ro->lon = $this->make_lonf(bindec(substr($_aisdata,61,28)));
			$ro->lat = $this->make_latf(bindec(substr($_aisdata,89,27)));
			$ro->cls = 1; // class A
		}
		else if ($ro->id == 5) {
			//$imo = bindec(substr($_aisdata,40,30));
			//$cs = $this->binchar($_aisdata,70,42);
			$ro->name = $this->binchar($_aisdata,112,120);
			$ro->cls = 1; // class A
		}
		else if ($ro->id == 18) {
			$ro->cog = bindec(substr($_aisdata,112,12))/10;
			$ro->sog = bindec(substr($_aisdata,46,10))/10;
			$ro->lon = $this->make_lonf(bindec(substr($_aisdata,57,28)));
			$ro->lat = $this->make_latf(bindec(substr($_aisdata,85,27)));
			$ro->cls = 2; // class B
		}
		else if ($ro->id == 19) {
			$ro->cog = bindec(substr($_aisdata,112,12))/10;
			$ro->sog = bindec(substr($_aisdata,46,10))/10;
			$ro->lon = $this->make_lonf(bindec(substr($_aisdata,61,28)));
			$ro->lat = $this->make_latf(bindec(substr($_aisdata,89,27)));
			$ro->name = $this->binchar($_aisdata,143,120);
			$ro->cls = 2; // class B
		}
		else if ($ro->id == 24) {
			$pn = bindec(substr($_aisdata,38,2));
			if ($pn == 0) {
				$ro->name = $this->binchar($_aisdata,40,120);
			}
			$ro->cls = 2; // class B
		}
		
        /* * * * * * * * * * * * * * * * * * * * * * * * * * *  
         * This is beginning of custom code for CRT project  *
         *                                                   */
        
        //echo "ro: :".var_dump($ro); // dump results here for demo purpose
        //Put ro data into LivePlot object
        if(is_object($ro)) {
            $id  = $ro->mmsi;
            $key  = 'mmsi'.$id;
            $name = $ro->name;
            $speed =$ro->sog;
            $lat   = $ro->lat;
            $lon   = $ro->lon;
            $course = $ro->cog;
            $ts   = $ro->ts;
            $dest = "";

            if(isset($this->plotDaemon->livePlot[$key])) {
                //Update only if data is new
                if($lat != $this->plotDaemon->livePlot[$key]->lat || $lon != $this->plotDaemon->livePlot[$key]->lon) {
                    $this->plotDaemon->livePlot[$key]->update($ts, $name, $lat, $lon, $speed, $course);
                    /*echo "livePlot[$key]->update(".date("F j, Y, g:i:s a", ($ts+getTimeOffset())).", ".$name
                      .", ".$lat.", ".$lon.", ".$speed.", ".$course.")\r\n";
					  */
                }  
            } else {
                //Skip river marker numbers
                if($id < 990000000) {
                    $this->plotDaemon->livePlot[$key] = new LivePlot($ts, $name, $id, $lat, $lon, $speed, $course, $this->plotDaemon);
                    echo "NEW livePlot[$key] (".date("F j, Y, g:i a", ($ts+getTimeOffset())).", ".$name.", ".$id.", ".$lat.", ".$lon.", ".$speed.", ".$course.")\r\n";
                } 
            }
        }
        /*                  End of custom CRT code           *
         * * * * * * * * * * * * * * * * * * * * * * * * * * */
		return $ro;
	}
}