<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Google\Cloud\Storage\StorageClient;

class UploadController extends Controller
{
    public function upload()
    {
        return view('uploads.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|max:10240',
        ]);

        $user = Auth::user();

        $googleConfigFile = file_get_contents(config_path('laravel-project.json'));

        $storage = new StorageClient([
            'keyFile' => json_decode($googleConfigFile, true)
        ]);

        $storageBucketName = config('googlecloud.storage_bucket');

        $bucket = $storage->bucket($storageBucketName);

        $avatar_request = $request->file('avatar');

        $image_path = $avatar_request->getRealPath();

        $avatar_name = Auth::user()->name.'-'.time().'.'.$avatar_request->extension();

        $fileSource = fopen($image_path, 'r');

        $googleCloudStoragePath = 'laravel-upload/' . $avatar_name;

        if(Auth::user()->avatar !== ''){
            $object = $bucket->object('laravel-upload/'.Auth::user()->avatar );
            $object->delete();
        };

        $bucket->upload($fileSource, [
            'predefinedAcl' => 'publicRead',
            'name' => $googleCloudStoragePath
        ]);

        $user->avatar = $avatar_name ;
        $user->save();

        return redirect()->route('home')
                        ->with('success','Uploaded successfully.');
    }

}
