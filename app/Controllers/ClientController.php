<?php

namespace App\Controllers;

use App\Database\Repositories\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;

class ClientController extends Controller
{
    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  int  $id
     *
     * @return Response
     */
    public function getShareXConfig(Request $request, Response $response, int $id): Response
    {
        $user = make(UserRepository::class)->get($request, $id, true);
    
        if (!$user->token) {
            $this->session->alert(lang('no_upload_token'), 'danger');
    
            return redirect($response, $request->getHeaderLine('Referer'));
        }
    
        $fileBaseName = $user->username . '-ShareX'; // Base file name without extension
        $fileName = $fileBaseName . '.sxcu'; // Full file name with extension
    
        $json = [
            'Version' => '16.1.0',
            'Name' => $fileBaseName,
            'DestinationType' => 'ImageUploader, TextUploader, FileUploader',
            'RequestMethod' => 'POST',
            'RequestURL' => route('upload'),
            'Body' => 'MultipartFormData',
            'Arguments' => [
                'file' => '{filename}',
                'text' => '{input}',
                'token' => $user->token,
            ],
            'FileFormName' => 'upload',
            'URL' => '{json:url}',
            'ThumbnailURL' => '{json:url}/raw',
            'DeletionURL' => '{json:url}/delete/' . $user->token,
        ];
    
        return json($response, $json, 200, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            ->withHeader('Content-Disposition', 'attachment;filename="' . $fileName . '"');
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  int  $id
     *
     * @return Response
     */
    public function getWindowsConfig(Request $request, Response $response, int $id): Response
    {
        // Retrieve the user
        $user = make(UserRepository::class)->get($request, $id, true);
    
        if (!$user->token) {
            $this->session->alert(lang('no_upload_token'), 'danger');
            return redirect($response, $request->getHeaderLine('Referer'));
        }
    
        // Load the app name from your config.php file
        $config = include __DIR__ . '/../../config.php'; // Adjust the path as necessary
        $appName = $config['app_name'] ?? 'XBackBone'; // Provide a default if not set

        // Define the file base name and extension
        $fileBaseName = $user->username . " - 'Send to' Windows Context Menu Script"; // Base file name without extension
        $fileName = $fileBaseName . '.bat'; // Full file name with extension
    
        // Prepare the content of the .txt file
        $batscript = '@echo off' . PHP_EOL;
        $batscript .= 'setlocal' . PHP_EOL;
        $batscript .= '' . PHP_EOL;
        $batscript .= 'if "%~1"=="" (' . PHP_EOL;
        $batscript .= '    call :CreateSendToShortcut' . PHP_EOL;
        $batscript .= '    exit /b' . PHP_EOL;
        $batscript .= ')' . PHP_EOL;
        $batscript .= '' . PHP_EOL;
        $batscript .= 'set "Token=' .$user->token. '"' . PHP_EOL;
        $batscript .= 'set "UploadUrl=' .route('upload'). '"' . PHP_EOL;
        $batscript .= '' . PHP_EOL;
        $batscript .= ':UploadToXBackBone' . PHP_EOL;
        $batscript .= 'curl -k -s -F "token=%Token%" -F "upload=@%1" %UploadUrl%' . PHP_EOL;
        $batscript .= 'echo.' . PHP_EOL;
        $batscript .= 'if %errorlevel% neq 0 (' . PHP_EOL;
        $batscript .= '    echo Upload failed. Curl error code: %errorlevel%' . PHP_EOL;
        $batscript .= ') else (' . PHP_EOL;
        $batscript .= '    echo File uploaded successfully to ' .$appName.'.' . PHP_EOL;
        $batscript .= ')' . PHP_EOL;
        $batscript .= 'exit /b' . PHP_EOL;
        $batscript .= '' . PHP_EOL;
        $batscript .= ':CreateSendToShortcut' . PHP_EOL;
        $batscript .= 'set "ShortcutName=Upload to ' .$appName. ' (@'.$user->username.').lnk"' . PHP_EOL;
        $batscript .= 'set "ShortcutPath=%APPDATA%\\Microsoft\\Windows\\SendTo\\%ShortcutName%"' . PHP_EOL;
        $batscript .= 'set "IconUrl=https://xbackbone.app/favicon.ico"' . PHP_EOL;
        $batscript .= 'set "IconPath=%APPDATA%\\Microsoft\\Windows\\SendTo\\xbackbone.ico"' . PHP_EOL;
        $batscript .= 'set "ScriptPath=%~dp0%~nx0"' . PHP_EOL;
        $batscript .= '' . PHP_EOL;
        $batscript .= 'curl -k -s -o "%IconPath%" "%IconUrl%" > nul 2>&1' . PHP_EOL;
        $batscript .= '' . PHP_EOL;
        $batscript .= 'echo Set oWS = WScript.CreateObject("WScript.Shell") > CreateShortcut.vbs' . PHP_EOL;
        $batscript .= 'echo sLinkFile = "%ShortcutPath%" >> CreateShortcut.vbs' . PHP_EOL;
        $batscript .= 'echo Set oLink = oWS.CreateShortcut(sLinkFile) >> CreateShortcut.vbs' . PHP_EOL;
        $batscript .= 'echo oLink.TargetPath = "cmd.exe" >> CreateShortcut.vbs' . PHP_EOL;
        $batscript .= 'echo oLink.Arguments = "/c ""%ScriptPath%""" >> CreateShortcut.vbs' . PHP_EOL;
        $batscript .= 'echo oLink.IconLocation = "%IconPath%" >> CreateShortcut.vbs' . PHP_EOL;
        $batscript .= 'echo oLink.Save >> CreateShortcut.vbs' . PHP_EOL;
        $batscript .= 'cscript /nologo CreateShortcut.vbs' . PHP_EOL;
        $batscript .= 'del CreateShortcut.vbs' . PHP_EOL;
        $batscript .= '' . PHP_EOL;
        $batscript .= 'echo Send To shortcut created: %ShortcutPath%' . PHP_EOL;
        $batscript .= 'exit /b' . PHP_EOL;
    
        // Return the .txt file as a downloadable response
        $response->getBody()->write($batscript);
    
        return $response
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment;filename="' . $fileName . '"');
    }

    /**
     * @param  Request  $request
     * @param  string|null  $token
     * @return Response
     * @throws \ZipStream\Exception\FileNotFoundException
     * @throws \ZipStream\Exception\FileNotReadableException
     * @throws \ZipStream\Exception\OverflowException
     * @throws HttpNotFoundException
     */
    public function getScreenCloudConfig(Request $request, string $token): Response
    {
        $user = $this->database->query('SELECT * FROM `users` WHERE `token` = ? LIMIT 1', $token)->fetch();
        if (!$user) {
            throw new HttpNotFoundException($request);
        }

        $config = [
            'token' => $token,
            'host' => route('root'),
        ];

        ob_end_clean();

        $options = new Archive();
        $options->setSendHttpHeaders(true);

        $zip = new ZipStream($user->username.'-screencloud.zip', $options);

        $zip->addFileFromPath('main.py', BASE_DIR.'resources/uploaders/screencloud/main.py');
        $zip->addFileFromPath('icon.png', BASE_DIR.'static/images/favicon-32x32.png');
        $zip->addFileFromPath('metadata.xml', BASE_DIR.'resources/uploaders/screencloud/metadata.xml');
        $zip->addFileFromPath('settings.ui', BASE_DIR.'resources/uploaders/screencloud/settings.ui');
        $zip->addFile('config.json', json_encode($config, JSON_UNESCAPED_SLASHES));

        $zip->finish();
        exit(0);
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  int  $id
     *
     * @return Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function getBashScript(Request $request, Response $response, int $id): Response
    {
        $user = make(UserRepository::class)->get($request, $id, true);

        if (!$user->token) {
            $this->session->alert(lang('no_upload_token'), 'danger');

            return redirect($response, $request->getHeaderLine('Referer'));
        }

        return view()->render(
            $response->withHeader('Content-Disposition', 'attachment;filename="xbackbone_uploader_'.$user->username.'.sh"'),
            'scripts/xbackbone_uploader.sh.twig',
            [
                'username' => $user->username,
                'upload_url' => route('upload'),
                'token' => $user->token,
            ]
        );
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  int  $id
     *
     * @return Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function getKDEScript(Request $request, Response $response, int $id): Response
    {
        $user = make(UserRepository::class)->get($request, $id, true);

        if (!$user->token) {
            $this->session->alert(lang('no_upload_token'), 'danger');

            return redirect($response, $request->getHeaderLine('Referer'));
        }

        return view()->render(
            $response->withHeader('Content-Disposition', 'attachment;filename="xbackbone_uploader_'.$user->username.'.sh"'),
            'scripts/xbackbone_kde_uploader.sh.twig',
            [
                'username' => $user->username,
                'upload_url' => route('upload'),
                'token' => $user->token,
            ]
        );
    }
}
