<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\websiteSetting;
class websiteSettingController extends Controller
{
    //
    
    // SettingsController.php  // Ensure you import the model

public function edit($id)
{
    // Fetch the existing data
    $setting = websiteSetting::find($id);

    // Pass the data to the view
    return view('website.form', compact('setting'));
}

public function update(Request $request, $id)
{
    // Validate incoming data
    $validatedData = $request->validate([
        'logo' => 'nullable|string|max:255',
        'content' => 'nullable|string',
        'title' => 'nullable|string|max:255',
        'meta_description' => 'nullable|string|max:255',
        'emails' => 'nullable|string|max:255',
        'contact_number' => 'nullable|string|max:255',
        'footer_text' => 'nullable|string|max:255',
    ]);

    // Find the record and update it
    $setting = websiteSetting::find($id);
    $setting->update($validatedData);

    // Redirect with success message
    return redirect()->route('website.form', $id)->with('success', 'Settings updated successfully');
}

}


