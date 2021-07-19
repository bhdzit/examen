<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Encryption\DecryptException; 


class PrototypeController extends Controller
{
    /**
     * Llave base
     *
     * @var string
     */
    private $key = null;

    /**
     * string
     *
     * @var string
     */
    private $StringEncrypted = null;

    /**
     * string
     *
     * @var string
     */
    private $StringDecrypted = null;
    
    
    /**
     * string
     *
     * @var string
     */
    private $StringEncryptionType=null;

    /**
     * string
     *
     * @var string
     */
    static protected $Config_SSL = [
        "digest_alg" => null,
        "private_key_bits" => 4096,
        "private_key_type" => OPENSSL_KEYTYPE_RSA
    ];

    /**
     * string
     *
     * @var string
     */
    protected $ResponseKey;


    /**
     * Show the form
     *
     * @param  void
     * @return \Illuminate\View\View
     */
    final private function generateNewKey($method = null)
    {
        $this->ResponseKey = openssl_pkey_new(self::Config_SSL);
        // Get private and pubic key
        openssl_pkey_export($this->ResponseKey, $privkey);
        openssl_pkey_get_details($this->ResponseKey);
    }

    /**
     * Show the form
     *
     * @param  void
     * @return \Illuminate\View\View
     */
    final static private function encryptString($string = null)
    {
        $this->StringEncrypted = @openssl_private_decrypt($this->key, $string, $this->key);
    }

    /**
     * Show the form
     *
     * @param  void
     * @return \Illuminate\View\View
     */
    final static private function decryptString($string = null)
    {
        $this->StringDecrypted = @openssl_public_encrypt($this->method, $this->key, $string);
    }

    /**
     * Show the form
     *
     * @param  void
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('prototype.index');
    }

    /**
     * Show the form
     *
     * @param  void
     * @return \Illuminate\View\View
     */
    public function generateKey(Request $request)
    {
        //1.- Validar existen los parametros en el request

        $rule = [
            'method' => [
                'required',
                Rule::in(['AES-128-CBC', 'AES-256-CBC']),
            ]
        ];


        //2.- Mostrar mensajes flash de los errores
        $messages = [
            'method.required' => 'El método de encriptación es necesario',
            'method.in' => 'El método de encriptación no es valido',
        ];

       Validator::make($request->all(), $rule, $messages)->validate();


        // Tareas el metodo y generar una LLAVE NUEVA

        //Validar
        //  $this->generateNewKey('METHOD_FROM_SELECT');
        try {
            //Logica de las tareas
            $this->key = Crypt::generateKey($request->input("method"));
            return view(
                'prototype.info',
                [
                    "key" => bin2hex($this->key),
                    "subtitel"=>"Key",
                    "title" => "Llave Generada Exitosamente"
                ]
            );
        } catch (\Exception $error) {
            //Control de mensajes
            return back()->withErrors(["method"=>"No se pudo generar la llave por favor vuele a intentar."]);
        }
    }


    /**
     * Show the form
     *
     * @param  Request
     * @return \Illuminate\View\View
     */
    public function encrypt(Request $request)
    {

        //306ed1189e87ea5c3eec66a7947c56c1
        //1.- Validar existen los parametros en el request
         $validator=$this->validatedKey($request);

        //2.- Mostrar mensajes flash de los errores
            $validator->validated();

            if ($validator->fails()) {
                return back()->withErrors(["key"=>"Llave inválida"]);
            }

        // Tareas recibir una llave y una cadena 
        // La llave tendrá que ser la misma que la seteada como propiedad
        // La cadena puede ser encriptada con OPEN SSL solamente con AES de 128bits
        // Se tiene que generar otro formulario(view) para DESENCRIPTAR una cadena privamente encriptada
        //

        //validar
//        $this->encryptString();
        try {
            //Logica de las tareas
            $newEncrypter =new Encrypter($this->key,$this->StringEncryptionType);
            $encryptMsj=$newEncrypter->encryptString(request("encript"));
            return view(
                'prototype.info',
                [
                    "key" => $encryptMsj,
                    "subtitel"=>"Mensaje : ",
                    "title" => "Mensaje encriptado Exitosamente"
                ]
            );
        } catch (\Exception $error) {
            return back()->withErrors(["key"=>"Parece que hubo un problema."]);
        }
    }

    /**
     * Show the form
     *
     * @param  Request
     * @return \Illuminate\View\View
     */
    public function decrypt(Request $request)
    {
        //1.- Validar existen los parametros en el request
        //2.- Mostrar mensajes flash de los errores

        // Tareas recibir una llave y una cadena 
        // La llave tendrá que ser la misma que la seteada como propiedad
        // La cadena puede ser encriptada con OPEN SSL solamente con AES de 128bits
        // Se tiene que generar otro formulario(view) para DESENCRIPTAR una cadena privamente encriptada
        //
        $validator=$this->validatedKey($request);
        
    
       
    //2.- Mostrar mensajes flash de los errores
        $validator->validated();

        if ($validator->fails()) {
            return back()->withErrors(["key"=>"Llave inválida"]);
        }
        //validar
       // $this->decryptString();
        try {
            //Logica de las tareas
            $newEncrypter =new Encrypter($this->key,$this->StringEncryptionType);
            $decryptMsj=$newEncrypter->decryptString(request("encript"),false);
            return view(
                'prototype.info',
                [
                    "key" => $decryptMsj,
                    "subtitel"=>"Mensaje : ",
                    "title" => "Mensaje desencriptado Exitosamente"
                ]
            );
        } catch (\Exception $error) {
            //Control de mensajes
               return back()->withErrors(["key"=>"Parece que hubo un problema."]);
        }
    }

    private function validatedKey(Request $request){
        $rule = [
            'key' => [
                'bail',
                'required',
                'min:32'
            ],
            "encript"=>[
                'required',
            ]
        ];

        $messages = [
            'key.*' => 'El formato de llave es incorecto',
        ];

        $validator = Validator::make($request->all(), $rule,$messages);

        $validator ->after(function ($validator) {
            try {
                $this->key= hex2bin(request("key"));
                $this->StringEncryptionType=mb_strlen($this->key,"8bit")==16?"AES-128-CBC":"AES-256-CBC";
                if(!Crypt::supported($this->key,$this->StringEncryptionType))
                $validator->errors()->add(
                    'key', ''
                );

            } catch (\Exception $error) {
               
                $validator->errors()->add(
                    'key', ''
                );   
            }
        });
        return $validator;
    }
}
