<?php

include "simplequery.class.php";

$q = new SimpleQuery("SELECT",'users');

$q->add_table('user_attribute_values','user_id','users.user_id','INNER')
  ->add_condition("users.user_id",'>','20','uid_gt_20')
  ->add_field('COUNT(*)')
  ->add_field('users.user_id')
  ->add_condition("users.email",'LIKE','%photosynth%','is_psc_address')
  ->add_condition("users.email",'LIKE','%gurdjieff.org%','is_greg_address')
  ->add_condition("users.user_id",'<','50','uid_lt_50')
  ->set_condition_structure(
    array(
      'uid_gt_20',
      'uid_lt_50',
      'OR' => array(
        'is_greg_address',
        'is_psc_address'
      )
    )
  );


echo $q->get();
