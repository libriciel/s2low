;La colonne broadcast_send_sources doit contenir une valeur
alter table actes_transactions alter column broadcast_send_sources set default 0;
alter table actes_transactions alter column broadcast_send_sources drop not null;
