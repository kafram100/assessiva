<?php

namespace Database\Seeders;

use App\Models\CustomerPackage;
use App\Models\AgentPackage;
use App\Models\PaymentConfig;
use App\Models\AdminStoreSetting;
use App\Models\ApiConfig;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            ['network_type' => 'MTN', 'package_name' => 'MTN 1GB', 'package_size' => '1GB', 'price' => 5.00, 'description' => 'MTN 1GB Data Bundle'],
            ['network_type' => 'MTN', 'package_name' => 'MTN 2GB', 'package_size' => '2GB', 'price' => 10.00, 'description' => 'MTN 2GB Data Bundle'],
            ['network_type' => 'MTN', 'package_name' => 'MTN 5GB', 'package_size' => '5GB', 'price' => 25.00, 'description' => 'MTN 5GB Data Bundle'],
            ['network_type' => 'VODAFONE', 'package_name' => 'Telecel 1GB', 'package_size' => '1GB', 'price' => 5.00, 'description' => 'Telecel 1GB Data Bundle'],
            ['network_type' => 'VODAFONE', 'package_name' => 'Telecel 2GB', 'package_size' => '2GB', 'price' => 10.00, 'description' => 'Telecel 2GB Data Bundle'],
            ['network_type' => 'AIRTELTIGO', 'package_name' => 'AirtelTigo 1GB', 'package_size' => '1GB', 'price' => 5.00, 'description' => 'AirtelTigo 1GB Data Bundle'],
            ['network_type' => 'AIRTELTIGO', 'package_name' => 'AirtelTigo 2GB', 'package_size' => '2GB', 'price' => 10.00, 'description' => 'AirtelTigo 2GB Data Bundle'],
        ];
        foreach ($packages as $p) {
            CustomerPackage::create($p);
        }

        CustomerPackage::create([
            'network_type' => 'MTN',
            'package_name' => 'MTN Mashup',
            'package_size' => '1 GB',
            'price' => 5.00,
            'description' => 'MTN Mashup - Manual fulfillment',
            'is_active' => true,
        ]);

        $agentPkgs = [
            ['role' => 'all', 'network_type' => 'MTN', 'package_name' => 'MTN 1GB', 'package_size_gb' => 1.0, 'package_size_mb' => 1024, 'price' => 4.00, 'sort_order' => 1],
            ['role' => 'all', 'network_type' => 'MTN', 'package_name' => 'MTN 2GB', 'package_size_gb' => 2.0, 'package_size_mb' => 2048, 'price' => 8.00, 'sort_order' => 2],
            ['role' => 'all', 'network_type' => 'MTN', 'package_name' => 'MTN 5GB', 'package_size_gb' => 5.0, 'package_size_mb' => 5120, 'price' => 20.00, 'sort_order' => 3],
            ['role' => 'all', 'network_type' => 'MTN', 'package_name' => 'MTN 10GB', 'package_size_gb' => 10.0, 'package_size_mb' => 10240, 'price' => 40.00, 'sort_order' => 4],
            ['role' => 'all', 'network_type' => 'VODAFONE', 'package_name' => 'Telecel 1GB', 'package_size_gb' => 1.0, 'package_size_mb' => 1024, 'price' => 4.00, 'sort_order' => 1],
            ['role' => 'all', 'network_type' => 'VODAFONE', 'package_name' => 'Telecel 2GB', 'package_size_gb' => 2.0, 'package_size_mb' => 2048, 'price' => 8.00, 'sort_order' => 2],
            ['role' => 'all', 'network_type' => 'VODAFONE', 'package_name' => 'Telecel 5GB', 'package_size_gb' => 5.0, 'package_size_mb' => 5120, 'price' => 20.00, 'sort_order' => 3],
            ['role' => 'all', 'network_type' => 'AIRTELTIGO', 'package_name' => 'AirtelTigo 1GB', 'package_size_gb' => 1.0, 'package_size_mb' => 1024, 'price' => 4.00, 'sort_order' => 1],
            ['role' => 'all', 'network_type' => 'AIRTELTIGO', 'package_name' => 'AirtelTigo 2GB', 'package_size_gb' => 2.0, 'package_size_mb' => 2048, 'price' => 8.00, 'sort_order' => 2],
            ['role' => 'all', 'network_type' => 'AIRTELTIGO', 'package_name' => 'AirtelTigo 5GB', 'package_size_gb' => 5.0, 'package_size_mb' => 5120, 'price' => 20.00, 'sort_order' => 3],
            ['role' => 'all', 'network_type' => 'MTN', 'package_name' => 'MTN Mashup', 'package_size_gb' => 1.0, 'package_size_mb' => 0, 'price' => 5.00, 'sort_order' => 99, 'manual_fulfillment' => true],
        ];
        foreach ($agentPkgs as $p) {
            AgentPackage::create($p);
        }

        $paymentConfigs = [
            ['config_key' => 'api_enabled', 'config_value' => '1', 'description' => 'Enable/disable external API integration'],
            ['config_key' => 'minimum_deposit', 'config_value' => '10.00', 'description' => 'Minimum deposit amount for agents'],
            ['config_key' => 'default_commission_rate', 'config_value' => '5.00', 'description' => 'Default commission rate for agents'],
            ['config_key' => 'payment_public_base_url', 'config_value' => '', 'description' => 'Public base URL for payment callbacks'],
        ];
        foreach ($paymentConfigs as $c) {
            PaymentConfig::create($c);
        }

        $storeSettings = [
            ['setting_key' => 'minimum_agent_markup', 'setting_value' => '5', 'description' => 'Minimum percentage markup agents must add to base price'],
            ['setting_key' => 'enable_sub_agents', 'setting_value' => '1', 'description' => 'Enable or disable Sub-Agent functionality'],
            ['setting_key' => 'default_agent_commission', 'setting_value' => '10', 'description' => 'Default commission percentage for Sub-Agents'],
            ['setting_key' => 'store_payment_methods', 'setting_value' => 'paystack,momo', 'description' => 'Available payment methods for stores'],
            ['setting_key' => 'auto_process_orders', 'setting_value' => '1', 'description' => 'Automatically process orders after payment'],
        ];
        foreach ($storeSettings as $s) {
            AdminStoreSetting::create($s);
        }

        $apiConfigs = [
            [
                'network_type' => 'mtn',
                'api_name' => 'Agent Portal MTN',
                'api_endpoint' => 'https://api.agentportalgh.com/api/queue/add',
                'api_key' => env('MTN_API_KEY', ''),
                'api_key_id' => env('MTN_API_KEY_ID', ''),
                'api_secret' => env('MTN_API_SECRET', ''),
                'request_method' => 'POST',
                'request_headers' => json_encode([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'X-API-Key' => '{api_key}',
                ]),
                'request_body_template' => json_encode([
                    'service' => '{network}',
                    'items' => [
                        ['msisdn' => '{phone}', 'data_gb' => '{data_gb}', 'reference' => '{order_id}'],
                    ],
                ]),
                'response_success_field' => 'added',
                'response_data_field' => null,
                'response_error_field' => 'error',
                'timeout_seconds' => 30,
                'retry_attempts' => 3,
                'status_endpoint' => 'https://api.agentportalgh.com/api/queue',
                'balance_endpoint' => 'https://api.agentportalgh.com/api/wallet',
                'is_active' => true,
            ],
            [
                'network_type' => 'telecel',
                'api_name' => 'Agent Portal Telecel',
                'api_endpoint' => 'https://api.agentportalgh.com/api/queue/add',
                'api_key' => env('VODAFONE_API_KEY', ''),
                'api_key_id' => env('VODAFONE_API_KEY_ID', ''),
                'api_secret' => env('VODAFONE_API_SECRET', ''),
                'request_method' => 'POST',
                'request_headers' => json_encode([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'X-API-Key' => '{api_key}',
                ]),
                'request_body_template' => json_encode([
                    'service' => '{network}',
                    'items' => [
                        ['msisdn' => '{phone}', 'data_gb' => '{data_gb}', 'reference' => '{order_id}'],
                    ],
                ]),
                'response_success_field' => 'added',
                'response_data_field' => null,
                'response_error_field' => 'error',
                'timeout_seconds' => 30,
                'retry_attempts' => 3,
                'status_endpoint' => 'https://api.agentportalgh.com/api/queue',
                'balance_endpoint' => 'https://api.agentportalgh.com/api/wallet',
                'is_active' => true,
            ],
            [
                'network_type' => 'airtel_tigo',
                'api_name' => 'Agent Portal AirtelTigo',
                'api_endpoint' => 'https://api.agentportalgh.com/api/queue/add',
                'api_key' => env('AIRTELTIGO_API_KEY', ''),
                'api_key_id' => env('AIRTELTIGO_API_KEY_ID', ''),
                'api_secret' => env('AIRTELTIGO_API_SECRET', ''),
                'request_method' => 'POST',
                'request_headers' => json_encode([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'X-API-Key' => '{api_key}',
                ]),
                'request_body_template' => json_encode([
                    'service' => '{network}',
                    'items' => [
                        ['msisdn' => '{phone}', 'data_gb' => '{data_gb}', 'reference' => '{order_id}'],
                    ],
                ]),
                'response_success_field' => 'added',
                'response_data_field' => null,
                'response_error_field' => 'error',
                'timeout_seconds' => 30,
                'retry_attempts' => 3,
                'status_endpoint' => 'https://api.agentportalgh.com/api/queue',
                'balance_endpoint' => 'https://api.agentportalgh.com/api/wallet',
                'is_active' => true,
            ],
        ];
        foreach ($apiConfigs as $c) {
            ApiConfig::create($c);
        }
    }
}
