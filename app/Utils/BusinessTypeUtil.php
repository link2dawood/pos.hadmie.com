<?php

namespace App\Utils;

class BusinessTypeUtil
{
    /**
     * Get all available business types
     *
     * @return array
     */
    public static function getBusinessTypes()
    {
        return [
            'restaurant_bar' => [
                'label' => __('business.business_type_restaurant_bar'),
                'modules' => ['tables', 'modifiers', 'service_staff', 'booking', 'kitchen'],
            ],
            'repair_shop' => [
                'label' => __('business.business_type_repair_shop'),
                'modules' => ['Repair'],
            ],
            'manufacturing' => [
                'label' => __('business.business_type_manufacturing'),
                'modules' => ['Manufacturing'],
            ],
            'essential_business' => [
                'label' => __('business.business_type_essential_business'),
                'modules' => [], // Essential modules are enabled by default - all modules allowed
            ],
            'hotel_management' => [
                'label' => __('business.business_type_hotel_management'),
                'modules' => ['Hms'],
            ],
            'hospital_management' => [
                'label' => __('business.business_type_hospital_management'),
                'modules' => ['Hms'],
            ],
            'school_management' => [
                'label' => __('business.business_type_school_management'),
                'modules' => [], // All modules allowed
            ],
            'gym_management' => [
                'label' => __('business.business_type_gym_management'),
                'modules' => [], // All modules allowed
            ],
        ];
    }

    /**
     * Get modules for a specific business type
     *
     * @param string $business_type
     * @return array
     */
    public static function getModulesForBusinessType($business_type)
    {
        $types = self::getBusinessTypes();
        
        if (isset($types[$business_type])) {
            return $types[$business_type]['modules'];
        }
        
        return [];
    }

    /**
     * Get business type label
     *
     * @param string $business_type
     * @return string
     */
    public static function getBusinessTypeLabel($business_type)
    {
        $types = self::getBusinessTypes();
        
        if (isset($types[$business_type])) {
            return $types[$business_type]['label'];
        }
        
        return $business_type;
    }

    /**
     * Check if business type is valid
     *
     * @param string $business_type
     * @return bool
     */
    public static function isValidBusinessType($business_type)
    {
        $types = self::getBusinessTypes();
        return isset($types[$business_type]);
    }
}

