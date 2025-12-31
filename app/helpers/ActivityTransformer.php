<?php

class ActivityTransformer
{
    public static function transform($activity)
    {
        if (empty($activity)) {
            return '';
        }
        
        return strtoupper(trim($activity));
    }
    
    public static function normalize($activity)
    {
        if (empty($activity)) {
            return '';
        }
        
        return ucfirst(strtolower(trim($activity)));
    }
    
    public static function sanitize($activity)
    {
        if (empty($activity)) {
            return '';
        }
        
        // Remove potentially dangerous characters
        $sanitized = preg_replace('/[<>"\']/', '', $activity);
        return trim($sanitized);
    }
}
