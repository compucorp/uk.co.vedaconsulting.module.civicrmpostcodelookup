-- Generated from drop.tpl
-- DO NOT EDIT.  Generated by CRM_Core_CodeGen
--
-- /*******************************************************
-- *
-- * Clean up the exisiting tables
-- *
-- *******************************************************/

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `paf_post_code_lookup`;

SET FOREIGN_KEY_CHECKS=1;
-- /*******************************************************
-- *
-- * Create new tables
-- *
-- *******************************************************/

-- /*******************************************************
-- *
-- * paf_post_code_lookup
-- *
-- * Store the Postcode Address File data
-- *
-- *******************************************************/
CREATE TABLE `paf_post_code_lookup` (
     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique PafPostcodeLookup ID',
     `post_code` varchar(12),
     `post_town` varchar(30),
     `dependent_locality` varchar(35),
     `double_dependent_locality` varchar(35),
     `thoroughfare_descriptor` varchar(80),
     `dependent_thoroughfare_descriptor` varchar(80),
     `building_number` varchar(4),
     `building_name` varchar(50),
     `sub_building_name` varchar(30),
     `po_box` varchar(6),
     `department_name` varchar(60),
     `organisation_name` varchar(60),
     `udprn` varchar(8),
     `postcode_type` varchar(3),
     `su_organisation_indicator` varchar(3),
     `delivery_point_suffix` varchar(3),
    PRIMARY KEY (`id`),
    INDEX `index_post_code`(post_code)
);

 
