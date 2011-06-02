<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Testslideshare extends CI_Controller {

	public function index()
	{
        $this->load->library('slideshare');
        
        // Query slides
        $params = array(
                'lang' => 'en',
                'q' => 'business'
            );
        $slides = $this->slideshare->search_slideshows( $params );
        
        echo 'Slides: '.count($slides);
        echo br(5);
        
        // Get single Slide 
        // The original data comes with first element as meta (total rows, pagination, etc)
        // So we get the first row with meta and slice it to get all the slides in a single array
        $slides_meta = $slides[0];
        $slides_rows = array_slice($slides, 1);
        
        
        $slide = $this->slideshare->get_slideshow( $slides_rows[1]['ID'] );
        
        echo 'Single Slide:';
        print_r($slide);
        echo br(5);
        
        
        $slide = $this->slideshare->get_slideshow_detailed( $slides_rows[1]['ID'] );
        echo 'Detailed Slide:';
        print_r($slide);
        echo br(5);
        
        
        $slides = $this->slideshare->get_slideshows_by_username( 'jmagnone' );
        echo 'Get slideshows by user:';
        print_r($slide);
        echo br(5);
        
        
        

	}
}

/* End of file testslideshare.php */
/* Location: ./application/controllers/testslideshare.php */