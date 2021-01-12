<?php

// Options :
//    $1 : nom du fichier
//    $2 : date
//    $3 : dry run ou pas
//Charger un fichier csv
//Pour chaque ligne
//-> vérifier que la date est ok (comparer col 2 à la date)
//-> prendre le SIRET (col 8 )
//-> rechercher l'id de l'autorité correspondante
//      $sql = "SELECT id FROM authorities where dia_siret=?";
//      return $this->queryOne($sql,$siret);
//-> modifier le poste comptable
//      helios_ftp_dest dans la table authorities
//      Correspondance :
//
// SL1V   VHPCE11
// SL2V   VHPCE21
// SL3V   VHPCE31
// SL5V   VHPCE51
// SL1M   MHPCE11
// SL2M   MHPCE21
// SL3M   MHPCE31
// SL4M   MHPCE41
// SL5M   MHPCE51