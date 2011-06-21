<?php 

class PagerHTML {
	
	public function getHTML($page_number,$nb_element_total,$nb_element_par_page){
				
		$nb_total_page = ceil($nb_element_total / $nb_element_par_page);
		
		
		$page = array(1,2,3,$page_number  - 1 , $page_number , $page_number +1,$nb_total_page-2,$nb_total_page-1,$nb_total_page );
		$page = array_unique($page);
		sort($page);
		foreach($page as $i => $nb_page){
			if ($nb_page>$nb_total_page || $nb_page<=0){
				unset($page[$i]);
			}
		}
		$last_page = 0;	
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
				<?php foreach ($page as $i) : ?>
					<?php if ($last_page + 1 != $i) :?>
						&nbsp;...&nbsp;
					<?php endif;?>
					<?php $last_page = $i; ?>
					<?php  if ($page_number == $i) : ?>
						<?php echo $i ?>
					<?php else: ?>
					<a href="<?php echo get_url(array("page" => $i)) ?>"
				 		title="Afficher la page <?php echo  $i ?>"> <?php echo $i ?></a>
					<?php endif; ?>
				<?php endforeach;?>
			</div>
			<div class="links_area">
				<?php if ($page_number > 1) : ?>
					<a href="<?php echo get_url(array("page" => ($page_number - 1))) ?>" title="Afficher la page précédente">&lt;&lt;&lt;</a>
				<?php else : ?>
	 				&lt;&lt;&lt;
				<?php endif;?>
				&nbsp;|&nbsp;
				<?php  if ($page_number < $nb_total_page) : ?>
					<a href="<?php echo get_url(array("page" => ($page_number + 1))) ?>" title="Afficher la page suivante">&gt;&gt;&gt;</a>
				<?php  else : ?>
					&gt;&gt;&gt;
				<?php  endif;?>
			</div>
		</div>
		<?php 		
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}
	
}