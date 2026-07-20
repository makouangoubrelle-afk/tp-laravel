<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mode d'ancrage
    |--------------------------------------------------------------------------
    | "polygon" enregistre réellement sur le réseau configuré.
    | "simulation" reste disponible uniquement pour le développement local.
    */
    'mode' => env('BLOCKCHAIN_MODE', 'simulation'),

    'network' => env('BLOCKCHAIN_NETWORK', 'polygon-amoy'),
    'chain_id' => (int) env('BLOCKCHAIN_CHAIN_ID', 80002),
    'rpc_url' => env('POLYGON_RPC_URL', 'https://polygon-amoy.drpc.org'),
    'public_rpc_url' => env('POLYGON_PUBLIC_RPC_URL', 'https://polygon-amoy-bor-rpc.publicnode.com'),
    'contract_address' => env('BLOCKCHAIN_CONTRACT_ADDRESS'),
    'private_key' => env('BLOCKCHAIN_PRIVATE_KEY'),
    'confirmations' => (int) env('BLOCKCHAIN_CONFIRMATIONS', 1),
    'timeout' => (int) env('BLOCKCHAIN_TIMEOUT', 120),
    'explorer_url' => env('BLOCKCHAIN_EXPLORER_URL', 'https://amoy.polygonscan.com'),
    'node_binary' => env('NODE_BINARY', 'node'),

    'allow_demo_wallet' => (bool) env('ALLOW_DEMO_WALLET', false),
];
