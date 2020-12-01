<?php

       $plan=array(
           'plan'=>'c1b1e557-f893-f2d0-6d75-1a0aeb3d520e',
           'description'=>'Agreement between A & B to convey beautiful data every day',
           'period'=>86400,
           'primary'=>array('company'=>'9aaad6b0-6f9d-3b3d-d9ef-2416f74149b5','admin'=>'soandso@suchandsuch.com','internal'=>'product categories and various private PIM minutia'),
           'secondary'=>array('company'=>'71194449-5032-e993-cf65-34dd8c759f61','admin'=>'thatguy@thatcompany.com','internal'=>'linecodes and various private syndication minutia')
           );

       echo base64_encode(json_encode($plan));

?>
