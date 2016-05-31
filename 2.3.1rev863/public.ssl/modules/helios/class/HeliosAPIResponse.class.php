<?php 

class HeliosAPIResponse {
	
	public function displayAndExit(array $array,$tag){
				
			header("Content-type: text/xml");
	 		echo "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
			?>
<<?php echo $tag?> >
	<?php $this->afficheArray($array,$tag);?>
</<?php echo $tag ?>>
			<?php 	
			exit();
	}
	
	private function afficheArray(array $array,$tag){
		foreach ($array as $cle => $value) {
			if (is_int($cle)) {
				echo "<$tag id='$cle'>";
			} else {
				echo "<$cle>";
			}
			if (is_array($value)){
				$this->afficheArray($value,$cle);
			} else {
				echo get_hecho($value);
			}
			if (is_int($cle)) {
				echo "</$tag>";
			} else {
				echo "</$cle>";
			}
			
		}
	}
	
	

}
	