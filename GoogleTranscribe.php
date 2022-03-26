<?php
error_reporting(E_ALL);
ini_set("display_errors", TRUE);

require_once 'vendor/autoload.php';

// text to speech
use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding as Text2SpeechAudioEncoding;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;

// speech to text
use Google\Cloud\Speech\V1\SpeechClient;
use Google\Cloud\Speech\V1\RecognitionAudio;
use Google\Cloud\Speech\V1\RecognitionConfig;
use Google\Cloud\Speech\V1\RecognitionConfig\AudioEncoding;

class GoogleTranscribe
{
    private $convert    = false;
    private $deleteFile = false;

    public function __construct()
    {
        putenv('GOOGLE_APPLICATION_CREDENTIALS=./g-service-acc-key.json');
    }

    public function textToSpeech()
    {
        try 
        {
            $request = (!empty(file_get_contents('php://input')) ? (json_decode(file_get_contents('php://input'))) : []);

            if (empty($request->text)) {
                echo json_encode([
                    'status'  => false,
                    'message' => 'The text field is required!',
                    'data'    => ''
                ]);
                exit();
            }        

            $textToSpeechClient = new TextToSpeechClient();
         
            $input = new SynthesisInput();
            $input->setText($request->text); 

            $voice = new VoiceSelectionParams();
            $voice->setLanguageCode('en-US');
             
            // optional
            $voice->setName('en-US-Standard-C');
         
            $audioConfig = new AudioConfig();
            $audioConfig->setAudioEncoding(Text2SpeechAudioEncoding::MP3);
         
            $resp = $textToSpeechClient->synthesizeSpeech($input, $voice, $audioConfig);
            $data = $resp->getAudioContent();
            $textToSpeechClient->close();

            if (!empty($data)) {
                echo json_encode([
                    'status'  => true,
                    'message' => 'Text to Speech Successful!',
                    'data'    => "data:audio/mpeg;base64,". base64_encode($data)
                ]);
                exit();
            } else {
                echo json_encode([
                    'status'  => false,
                    'message' => 'Text to Speech Failed!',
                    'data'    => ""
                ]);
                exit();
            }

        } 
        catch(Exception $e) 
        {
            echo json_encode([
                'status'  => false,
                'message' => 'Something went wrong!',
                'data'    => $e->getMessage()
            ]);
            exit();
        }
    }

    public function speechToText()
    {
        try 
        {
            $content = (!empty(file_get_contents('php://input')) ? file_get_contents('php://input') : null);

            if (empty($content)) {
                echo json_encode([
                    'status'  => false,
                    'message' => 'The voice/stream-audio file is required!',
                    'data'    => ''
                ]);
                exit();
            } 
 
            $filePath = "./assets/voice/".time().".aac";
            $modifiedFilePath = $filePath;
            file_put_contents($filePath, $content);

            if ($this->convert) {
                $mimeType = mime_content_type($filePath);
                if($mimeType == 'application/octet-stream') {
                    $modifiedFilePath = $filePath.'.aac';
                    @shell_exec("ffmpeg -i {$filePath} -map 0:a -c:a copy  {$modifiedFilePath}");
                }
            } 

            // get contents of a file into a string
            $content = file_get_contents($modifiedFilePath); 

            // set string as audio content
            $audio = (new RecognitionAudio())
                ->setContent($content);

            // set config
            $config = (new RecognitionConfig())
                ->setEncoding(AudioEncoding::WEBM_OPUS)
                ->setSampleRateHertz(48000)
                ->setLanguageCode('en-US')
                ->setEnableAutomaticPunctuation(true);

            try {

                // create the speech client
                $client = new SpeechClient();

                // make the API call
                $response = $client->recognize($config, $audio);
                $results = $response->getResults();

                $transcript = "";
                $confidence = 0;
                foreach ($results as $result) {
                    $alternatives = $result->getAlternatives();
                    $mostLikely = $alternatives[0];
                    $transcript .= $mostLikely->getTranscript();
                    $confidence += $mostLikely->getConfidence();
                }

                echo json_encode([
                    'status'  => true,
                    'message' => 'transcript successful!',
                    'data'    => [
                        'transcript' => $transcript,
                        'confidence' => $confidence
                    ] 
                ]);


                // delete saved files
                if ($this->deleteFile) {
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                    if (file_exists($modifiedFilePath)) {
                        @unlink($modifiedFilePath);
                    }
                }
                exit();

            } finally {
                $client->close();
            }


            echo json_encode([
                'status'  => false,
                'message' => 'Transcript failed!',
                'data'    => $operation->getError() 
            ]);
            exit();



            // $client->close();

            // if ($operation->operationSucceeded()) {
            //     $response = $operation->getResult();

            //     // each result is for a consecutive portion of the audio. iterate
            //     // through them to get the transcripts for the entire audio file.
            //     $transcript = "";
            //     $confidence = 0;
            //     foreach ($response->getResults() as $key => $result) {
            //         $alternatives = $result->getAlternatives();
            //         $mostLikely = $alternatives[0];
            //         $transcript .= $mostLikely->getTranscript();
            //         $confidence += $mostLikely->getConfidence();
            //         // printf($key .'Transcript: %s' . PHP_EOL, $transcript);
            //         // printf('Confidence: %s' . PHP_EOL, $confidence);
            //     }

            //     echo json_encode([
            //         'status'  => true,
            //         'message' => 'transcript successful!',
            //         'data'    => [
            //             'transcript' => $transcript,
            //             'confidence' => $confidence,
            //             'original_path'  => $filePath,
            //             'modified_path'  => $modifiedFilePath
            //         ] 
            //     ]);

            //     // delete saved files
            //     if ($this->deleteFile) {
            //         if (file_exists($filePath)) {
            //             @unlink($filePath);
            //         }
            //         if (file_exists($modifiedFilePath)) {
            //             @unlink($modifiedFilePath);
            //         }
            //     }

            //     exit();
            // } else {

            //     echo json_encode([
            //         'status'  => false,
            //         'message' => 'Transcript failed!',
            //         'data'    => $operation->getError() 
            //     ]);
            //     exit();
            // }


        } catch(Exception $e) {

            echo json_encode([
                'status'  => false,
                'message' => 'Something went wrong!',
                'data'    => $e->getMessage()
            ]);
            exit();
        }
    }

}
 


$gt = new GoogleTranscribe;


$request = (!empty(file_get_contents('php://input')) ? (json_decode(file_get_contents('php://input'))) : []);

if (!empty($request->text)) {
    echo $gt->textToSpeech();
} else {
    echo $gt->speechToText();
}

