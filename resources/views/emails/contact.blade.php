<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Message de contact</title></head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #333;">
    <h2 style="color: #2563eb; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;">Nouveau message de contact</h2>

    <table style="width: 100%; margin-bottom: 20px;">
        <tr>
            <td style="padding: 8px 0; font-weight: bold; width: 100px;">De :</td>
            <td style="padding: 8px 0;">{{ $data['name'] }} &lt;{{ $data['email'] }}&gt;</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-weight: bold;">Sujet :</td>
            <td style="padding: 8px 0;">{{ $data['subject'] }}</td>
        </tr>
    </table>

    <div style="background: #f9fafb; border-left: 4px solid #2563eb; padding: 15px; border-radius: 4px;">
        <p style="margin: 0; white-space: pre-wrap;">{{ $data['message'] }}</p>
    </div>

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">
    <p style="color: #9ca3af; font-size: 12px;">{{ config('app.name') }} - {{ now()->format('d/m/Y H:i') }}</p>
</body>
</html>
