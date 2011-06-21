<?php 

class PagerHTML {
	
	public function getHTML($page_number,$nb_element_total,$nb_element_par_page){
				
		$nb_total_page = ceil($nb_element_total / $nb_element_par_page);
		ob_start();
		?>
		<div id="pager">
			<h1>Pagination</h1>
			<h2>Afficher par page&nbsp;:</h2>
			<div class="links_area">
				<?php foreach (array(10, 20, 50, 100) as $val) : ?>
					<?php if ($nb_element_par_page == $val) : ?>
						&nbsp;<?php echo $val ?>&nbsp;
					<?php else : ?>
						<a href="<?php echo get_url(array("count" => $val)) ?>" 
							title="Afficher <?php echo $val ?>  éléments par page">
							<?php echo $val?>
						</a>
					<?php endif;?>
				<?php endforeach;?>
			</div>
			<h2>Page&nbsp;:</h2>
			<div class="links_area">
				<?php for ($i = 1; $i <= $nb_total_page; $i++) : ?>
					<?php  if ($page_number == $i) : ?>
						<?php echo $i ?>
					<?php else: ?>
					<a href="<?php echo get_url(array("page" => $i)) ?>"
				 		title="Afficher la page <?php echo  $i ?>"> <?php echo $i ?></a>
					<?php endif; ?>
				<?php endfor;?>
			</div>
			<div class="links_area">
				<?php if ($page_number > 1) : ?>
					<a href="<?php echo get_url(array("page" => ($page_number - 1))) ?>" title="Afficher la page précédente">&lt;&lt;&lt;</a>
				<?php else : ?>
	 				&lt;&lt;&lt;
				<?php endif;?>
				&nbsp;|&nbsp;
				<?php if ($page_number < $nb_total_page) : ?>
					<a href="<?php echo get_url(array("page" => ($page_number + 1))) ?>" title="Afficher la page suivante">&gt;&gt;&gt;</a>
				<?php else : ?>
					&gt;&gt;&gt;
				<?php endif;?>
			</div>
		</div>
		<?php 		
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}
	
}