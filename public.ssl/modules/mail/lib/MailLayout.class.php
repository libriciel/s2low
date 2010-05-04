<?php
/*
 * T�D�TIS - Copyright 2006 Alternance-Soft
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant �  la
 * dématérialisation de l'administration. 
 *
 * Ce logiciel est régi par la licence CeCILL soumise au droit français et
 * respectant les principes de diffusion des logiciels libres. Vous pouvez
 * utiliser, modifier et/ou redistribuer ce programme sous les conditions
 * de la licence CeCILL telle que diffusée par le CEA, le CNRS et l'INRIA 
 * sur le site "http://www.cecill.info".
 *
 * En contrepartie de l'accessibilité au code source et des droits de copie,
 * de modification et de redistribution accordés par cette licence, il n'est
 * offert aux utilisateurs qu'une garantie limitée.  Pour les mêmes raisons,
 * seule une responsabilité restreinte pèse sur l'auteur du programme,  le
 * titulaire des droits patrimoniaux et les concédants successifs.
 *
 * A cet égard  l'attention de l'utilisateur est attirée sur les risques
 * associés au chargement,  �  l'utilisation,  �  la modification et/ou au
 * développement et �  la reproduction du logiciel par l'utilisateur étant 
 * donné sa spécificité de logiciel libre, qui peut le rendre complexe �  
 * manipuler et qui le réserve donc �  des développeurs et des professionnels
 * avertis possédant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invités �  charger  et  tester  l'adéquation  du
 * logiciel �  leurs besoins dans des conditions permettant d'assurer la
 * sécurité de leurs systèmes et ou de leurs données et, plus généralement, 
 * � l'utiliser et l'exploiter dans les mêmes conditions de sécurité. 
 *
 * Le fait que vous puissiez accéder �  cet en-tête signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez accepté les
 * termes.
*/

/**
 * \class MailLayout.class.php
 * \brief layout pour module mail. 
 *  		appel� par index.
 * \author TH ,JMontiel
 * \date :23-04-2008
 * 
 *
 * cette class permet de d�finir la style de mail systeme
 * h�rit� par HTMLayout.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */
  require_once (SITEROOT. "class/Layout.class.php");
  class MailLayout extends HTMLLayout
  {

  	 public function DisplayHead()
  	 {
	    $this->includeErrors();
	
	    if ($this->template) {
	      require_once(HTML_TEMPLATE_PATH .'/'. $this->template);
	    } else {
	      echo "<?xml version=\"1.0\" encoding=\"iso-8859-15\"?>\n";
	      echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\" \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n";
	      echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"fr\">\n";
	      echo "<head>\n";
	      echo "<title>" . $this->title . "</title>\n";
	      echo $this->header . "\n";
	      echo "</head>\n";
	      echo "<body>\n";
	      echo $this->body . "\n";
	     
	    }
    }

  	 public function DisplayFoot()
  	 {
  	 	 $this->buildFooter(true);
  	 	 echo "</body>\n";
  	 }
  }
?>