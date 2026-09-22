<?php
final class Evidencia {
    public const DIRECTORIO = __DIR__.'/../storage/evidencias/';
    public static function guardar(array $archivo): string {
        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'] ?? '')) throw new DomainException('Adjunta una imagen o PDF válido.');
        if ($archivo['size'] > 10 * 1024 * 1024) throw new DomainException('La evidencia no puede superar 10 MB.');
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
        $extension=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','application/pdf'=>'pdf'][$mime] ?? null;
        if (!$extension) throw new DomainException('Solo se aceptan JPG, PNG, WEBP o PDF.');
        $nombre=bin2hex(random_bytes(24)).'.'.($extension==='pdf' ? 'pdf' : 'jpg');
        $destino=self::DIRECTORIO.$nombre;
        if ($extension==='pdf') {
            if (file_get_contents($archivo['tmp_name'],false,null,0,5)!=='%PDF-') throw new DomainException('El PDF no es válido.');
            if (!move_uploaded_file($archivo['tmp_name'],$destino)) throw new RuntimeException('No se pudo guardar PDF');
        } else {
            $size=@getimagesize($archivo['tmp_name']);
            if (!$size || $size[0]*$size[1]>24000000) throw new DomainException('La imagen es demasiado grande o no es válida (máximo 24 megapíxeles).');
            $original=@imagecreatefromstring(file_get_contents($archivo['tmp_name']));
            if (!$original) throw new DomainException('No se pudo leer la imagen.');
            $factor=min(1,1600/max($size[0],$size[1])); $w=max(1,(int)($size[0]*$factor)); $h=max(1,(int)($size[1]*$factor));
            $nueva=imagecreatetruecolor($w,$h); imagefill($nueva,0,0,imagecolorallocate($nueva,255,255,255));
            imagecopyresampled($nueva,$original,0,0,0,0,$w,$h,$size[0],$size[1]);
            $ok=imagejpeg($nueva,$destino,82); imagedestroy($nueva); imagedestroy($original);
            if (!$ok) throw new RuntimeException('No se pudo guardar imagen');
        }
        return $nombre;
    }
}
