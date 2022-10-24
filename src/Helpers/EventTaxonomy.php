<?php


namespace App\Helpers;

class EventTaxonomy
{
  const LIFE_EVENTS = [
    'taxonomies.life_events.enrolling_in_school_university_and_or_applying_for_a_study_grant' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/life-event/1',
    'taxonomies.life_events.disability' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/life-event/2',
    'taxonomies.life_events.looking_for_a_job_starting_a_new_job_becoming_unemployed' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/life-event/3',
    'taxonomies.life_events.retirement' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/life-event/4',
    'taxonomies.life_events.applying_to_a_drivers_licence_or_renewing_an_existing_one' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/life-event/5',
    'taxonomies.life_events.registering_owning_a_vehicle' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/life-event/6',
    'taxonomies.life_events.access_to_public_transportation' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/life-event/7',
    'taxonomies.life_events.buying_renting_a_house_building_land_building_renovating_a_house_building' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/life-event/8',
    'taxonomies.life_events.moving_and_changing_address_within_one_country' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/life-event/9',
    'taxonomies.life_events.moving_or_preparing_to_move_to_another_country_eg_to_study_work_retire' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/life-event/10',
    'taxonomies.life_events.needing_a_passport_visa_or_assistance_to_travel_to_another_country' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/life-event/11',
    'taxonomies.life_events.declaring_the_birth_of_a_child_and_or_applying_for_a_birth_grand' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/life-event/12',
    'taxonomies.life_events.marrying_and_or_changing_marital_status' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/life-event/13',
    'taxonomies.life_events.death_of_a_close_relative_and_or_starting_an_inheritance_procedure' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/life-event/14',
    'taxonomies.life_events.making_cancelling_a_doctors_appointment' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/life-event/15',
    'taxonomies.life_events.reporting_a_crime' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/life-event/16',
    'taxonomies.life_events.declaring_income_taxes_paying_contributions' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/life-event/17',
    'taxonomies.life_events.access_to_public_cultural_sites' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/life-event/18',
    'taxonomies.life_events.owning_taking_care_losing_pets' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/life-event/19',
  ];

  const BUSINESS_EVENTS = [
    'taxonomies.business_events.starting_a_company' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/business-event/1',
    'taxonomies.business_events.starting_a_new_activity' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/business-event/2',
    'taxonomies.business_events.applying_for_licenses_permits_and_certificates' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/business-event/3',
    'taxonomies.business_events.registering_a_cross_border_business' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/business-event/4',
    'taxonomies.business_events.starting_registering_a_branch' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/business-event/5',
    'taxonomies.business_events.financing_a_company' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/business-event/6',
    'taxonomies.business_events.staffing' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/business-event/7',
    'taxonomies.business_events.paying_taxes_vat_and_customs' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/business-event/8',
    'taxonomies.business_events.reporting_and_notifying_authorities' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/business-event/9',
    'taxonomies.business_events.closing_a_company_a_professional_activity' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/business-event/10',
    'taxonomies.business_events.closing_a_branch' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/business-event/11',
    'taxonomies.business_events.restructuring_company' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/business-event/12',
    'taxonomies.business_events.selling_company' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/business-event/13',
    'taxonomies.business_events.bankruptcy' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/business-event/14',
    'taxonomies.business_events.participating_in_public_procurement_national_and_cross_border' => 'https://ontopia-lodview.agid.gov.it/controlled-vocabulary/classifications-for-public-services/life-business-event/business-event/15',
  ];
}
