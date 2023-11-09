<?php

namespace GenWP;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class OpenAIGenerator {
    private $api_key;
    private $model;
    private $max_tokens;
    private $temperature;
    private $top_p;
    private $frequency_penalty;
    private $presence_penalty;
    private $http_args;
    private $base_url;

    public function __construct() {
        $settings = get_option('genwp_settings', array());

        $this->api_key = get_option('genwp_genwp-openai_api_key');
        $this->model = isset($settings['model']) ? $settings['model'] : 'text-davinci-003';
       // $this->max_tokens = isset($settings['max_tokens']) ? (int) $settings['max_tokens'] : 10000;
        $this->temperature = isset($settings['temperature']) ? (float) $settings['temperature'] : 0.7;
        $this->top_p = isset($settings['top_p']) ? (float) $settings['top_p'] : 1.0;
        $this->frequency_penalty = isset($settings['frequency_penalty']) ? (float) $settings['frequency_penalty'] : 0.0;
        $this->presence_penalty = isset($settings['presence_penalty']) ? (float) $settings['presence_penalty'] : 0.0;
        $this->base_url = 'https://api.openai.com/v1/';

        $this->http_args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
            ),
            'timeout' => 1000,
        );
    }

    private function get_api_url($endpoint) {
        $endpoints = array(
            'chat' => $this->base_url . 'chat/completions',
            'completion' => $this->base_url . 'completions',
            'image' => $this->base_url . 'images/generations',
        );
        return $endpoints[$endpoint];
    }

    private function generate($endpoint, $body, $args = array()) {
        $api_url = $this->get_api_url($endpoint);
        
        if ($endpoint !== 'image') {
            $defaults = array(
                // 'max_tokens' => $this->max_tokens,
                'model' => $this->model,
                'temperature' => $this->temperature,
                'top_p' => $this->top_p,
                'frequency_penalty' => $this->frequency_penalty,
                'presence_penalty' => $this->presence_penalty,
                'n' => 1
            );

            $args = wp_parse_args($args, $defaults);

            $request_body = array_merge($body, $args);
        } else {
            $request_body = $body;
        }

        $response = wp_remote_post($api_url, array_merge($this->http_args, array('body' => wp_json_encode($request_body))));

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        switch($response_code) {
            case 401:
                throw new \Exception('Invalid Authentication: Ensure the correct API key and requesting organization are being used.');
            case 429:
                throw new \Exception('Rate limit reached for requests: Pace your requests or check your maximum monthly spend.');
            case 500:
                throw new \Exception('The server had an error while processing your request: Retry your request after a brief wait and contact us if the issue persists.');
            case 503:
                throw new \Exception('The engine is currently overloaded, please try again later.');
        }

        $response_body = json_decode(wp_remote_retrieve_body($response), true);
           
        if (isset($response_body['choices'][0]['text'])) {
            return $response_body['choices'][0]['text'];
        } elseif (isset($response_body['choices'][0]['message']['content'])) {
            return $response_body['choices'][0]['message']['content'];
        } elseif (isset($response_body['data'][0]['url'])) {
            return $response_body['data'][0]['url'];
        } elseif (isset($response_body['data'][0]['b64_json'])) {
            return $response_body['data'][0]['b64_json'];
        }

        throw new \Exception('Unexpected API response');
    }
   
    public function generate_completion($prompt, $args = array()) {
		$system_message = "The assistant is an experienced writer who produces detailed and informative (2000+ words) articles about the topic. The assistant uses a human-like writing style that is always formal and professional, utilizing active voice, personification and varied sentence structures to create an engaging flow and pace. The assistant must organize the content using Markdown formatting, specifically the CommonMark syntax.";

		if ($this->model == 'gpt-4' || $this->model == 'gpt-3.5-turbo-16k' || $this->model == 'gpt-4-1106-preview' || $this->model == 'gpt-3.5-turbo-1106') {
			// Chat Model
			$messages = array(
				array(
					"role" => "system", 
					"content" => $system_message
				),
				array("role" => "user", "content" => $prompt)
			);
			$body = array('messages' => $messages);
			return $this->generate('chat', $body, $args);

		} else {
			// Traditional model: use prompt
			return $this->generate('completion', array('prompt' => $prompt), $args);
		}
	}

    public function generate_image($description, $n = 1, $size = '256x256', $args = array()) {
        $body = array(
            'prompt' => $description,
            'n' => $n,
            'size' => $size,
            'response_format' => 'url',
        );
    
        $response = $this->generate('image', $body, $args);
    
        if (is_string($response)) {
            return array($response);
        }
    
        return array_map(function($item) {
            return $item['url'];
        }, $response['data']);
    
        throw new \Exception('Unexpected API response when generating image');
    }
    
}