<?php


namespace GenTech\QuickMessage;

class SendMessage
{
    public static function send($source, array $destination, $message)
    {

        $contacts = (new SendMessage)->checkPhoneNumber($destination);
        $valid_contacts = implode(",",$contacts['valid_contact']);
        $api_key = config('quickmessage.api_key');
        $destination = $valid_contacts;
        $from = $source;
        $campaign = config('quickmessage.campaign');
        $routeid = config('quickmessage.routeid');
        $base_url = config('quickmessage.base_url');
        $sms_text = urlencode($message);

        //Submit to server

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $base_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "key=".$api_key."&campaign=$campaign&routeid=$routeid&type=text&contacts=".$destination."&senderid=".$from."&msg=".$sms_text);
        $response = curl_exec($ch);
        curl_close($ch);

        if (str_starts_with($response, "ERR")){
            return response()->json([
                'error' => true,
                'message' => $response,
                'recipient' => $contacts['total_valid_contact'],
                'total_valid_contact' =>  $contacts['total_valid_contact'],
                'total_invalid_contact' =>  $contacts['total_invalid_contact'],
            ]);
        }

        return response()->json([
            'error' => false,
            'message' => 'Successfully sent',
            'short_id' => substr($response, strrpos($response, '/' )+1),
            'recipient' => $contacts['total_valid_contact'],
            'total_valid_contact' =>  $contacts['total_valid_contact'],
            'total_invalid_contact' =>  $contacts['total_invalid_contact'],
        ]);
    }

    public static function delivery(string $sms_short_id)
    {
        $api_key = config('quickmessage.api_key');
        $sms_shoot_id = $sms_short_id;
        $dlr_base_url = config('quickmessage.dlr_base_url');

        $api_url = "$dlr_base_url/".$api_key."/getDLR/".$sms_shoot_id;

        $delivered = 0;
        $pending = 0;
        $failed = 0;
        $OperatorSubmitted = 0;

        //Submit to server

        $response = file_get_contents( $api_url);
        $dlr_array = json_decode($response);

        if (str_starts_with($response, "ERR")){
            return response()->json([
                'error' => true,
                'message' => $response
            ]);
        }

        $report = [];
        foreach ($dlr_array as $key => $value) {
            if ($value->DLR == "Delivered") {
                $delivered = $delivered+1;
                $report[] = [
                    'phone_number' => $value->MSISDN,
                    'dlr' => $value->DLR,
                    'description' => $value->DESC
                ];
            }elseif ($value->DLR == "Submitted") {
                $OperatorSubmitted = $OperatorSubmitted+1;
                $report[] = [
                    'phone_number' => $value->MSISDN,
                    'dlr' => $value->DLR,
                    'description' => $value->DESC
                ];
            }elseif ($value->DLR == "Pending") {
                $pending = $pending+1;
                $report[] = [
                    'phone_number' => $value->MSISDN,
                    'dlr' => $value->DLR,
                    'description' => $value->DESC
                ];
            }elseif ($value->DLR == "Failed") {
                $failed = $failed+1;
                $report[] = [
                    'phone_number' => $value->MSISDN,
                    'dlr' => $value->DLR,
                    'description' => $value->DESC
                ];
            }else{
                $delivered = $delivered+1;
                $report[] = [
                    'phone_number' => $value->MSISDN,
                    'dlr' => $value->DLR,
                    'description' => $value->DESC
                ];
            }
        }

        $general_report = [
            'report' => $report,
            'delivered' => $delivered,
            'OperatorSubmitted' => $OperatorSubmitted,
            'pending' => $pending,
            'failed' => $failed,
        ];
        return response()->json($general_report);
    }

    private final function checkPhoneNumber(array $contacts)
    {
        $valid_contacts = [];
        $invalid_contacts = [];
        $total_valid_contact = 0;
        $total_invalid_contact = 0;
        foreach ($contacts as $key => $contact){
            if (str_starts_with($contact, '+') && strlen($contact) == 13) {
                $valid_contacts[] = str_replace('+', '', $contact);
                $total_valid_contact += 1;
            }elseif (str_starts_with($contact, '0') && strlen($contact) == 10) {
                $valid_contacts[] = str_replace('0', '255', $contact);
                $total_valid_contact += 1;
            }elseif (str_starts_with($contact, '255') && strlen($contact) == 12) {
                $valid_contacts[] = $contact;
                $total_valid_contact += 1;
            }elseif (strlen($contact) == 9 && is_numeric($contact)) {
                $valid_contacts[]= '255'.$contact;
                $total_valid_contact += 1;
            }else{
                $invalid_contacts[] = $contact;
                $total_invalid_contact += 1;
            }
        }

        return [
            'valid_contact' => $valid_contacts,
            'invalid_contact' => $invalid_contacts,
            'total_valid_contact' => $total_valid_contact,
            'total_invalid_contact' => $total_invalid_contact
        ];
    }
}
