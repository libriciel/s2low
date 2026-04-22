<?php

namespace S2lowLegacy\Class;

class PagerHTML
{
    public function getHTML($page_number, $nb_element_total, $nb_element_par_page)
    {

        $nb_total_page = ceil($nb_element_total / $nb_element_par_page);


        $page = array(1,2,3,$page_number  - 1 , $page_number , $page_number + 1,$nb_total_page - 2,$nb_total_page - 1,$nb_total_page );
        $page = array_unique($page);
        sort($page);
        foreach ($page as $i => $nb_page) {
            if ($nb_page > $nb_total_page || $nb_page <= 0) {
                unset($page[$i]);
            }
        }
        $last_page = 0;
        ob_start();
        ?>
                    <div id="display-items">
                        <h2>Afficher par page</h2>
                        <ul class="pagination pagination-sm">
                            <?php foreach (array(10, 20, 50, 100) as $val) : ?>
                                <?php if ($nb_element_par_page == $val) : ?>
                                    <li class="disabled"><a href="#"><?php echo $val ?></a></li>
                                <?php else : ?>
                                    <li>
                                        <a href="<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getURLWithParam(array("count" => $val,"page" => 1)) ?>"
                                                title="Afficher <?php echo $val ?>  éléments par page">
                                                <?php echo $val?>
                                        </a>
                                    </li>
                                <?php endif;?>
                            <?php endforeach;?>
                        </ul>
                    </div>
                    <div id="pages">
                        <h2>Page</h2>
                        <ul class="pagination pagination-sm">
                            <?php if ($page_number > 1) : ?>
                            <li>
                                <a href="<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getURLWithParam(array("page" => ($page_number - 1))) ?>" title="Afficher la page précédente">&laquo;</a>
                            </li>
                            <?php else : ?>
                                <li class="disabled"><a href="#">&laquo;</a></li>
                            <?php endif;?>

                            <?php foreach ($page as $i) : ?>
                                    <?php if ($last_page + 1 != $i) :?>
                                    <li class="disabled">
                                        <a href="#">...</a>
                                    </li>
                                    <?php endif;?>
                                    <?php $last_page = $i; ?>
                                    <?php  if ($page_number == $i) : ?>
                                    <li class="active">
                                        <a href="#" title="page courante"><?php echo $i ?></a>
                                    </li>
                                    <?php else : ?>
                                    <li>
                                        <a href="<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getURLWithParam(array("page" => $i)) ?>"
                                            title="Afficher la page <?php echo  $i ?>"> 
                                            <?php echo $i ?>
                                        </a>
                                    </li>    
                                    <?php endif; ?>
                            <?php endforeach;?>
                            <?php  if ($page_number < $nb_total_page) : ?>
                                <li>
                                    <a href="<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getURLWithParam(array("page" => ($page_number + 1))) ?>" title="Afficher la page suivante">&raquo;</a>
                                </li>
                            <?php  else : ?>
                                <li class="disabled"><a href="#">&raquo;</a></li>
                            <?php  endif;?>
                        </ul>
                    </div>
        <?php
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }
}
