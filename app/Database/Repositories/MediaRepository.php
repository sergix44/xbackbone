<?php

namespace App\Database\Repositories;

use App\Database\DB;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use League\Flysystem\Plugin\ListWith;

class MediaRepository
{
    public const PER_PAGE = 21;
    public const PER_PAGE_ADMIN = 27;

    public const ORDER_TIME = 0;
    public const ORDER_NAME = 1;
    public const ORDER_SIZE = 2;

    /** @var DB */
    protected $db;

    /** @var bool */
    protected $isAdmin;

    protected $userId;

    /** @var int */
    protected $orderBy;

    /** @var string */
    protected $orderMode;

    /** @var string */
    protected $text;

    /** @var Filesystem */
    protected $storage;

    private $pages;
    private $media;

    /**
     * @var int
     */
    private $tagId;

    /**
     * MediaQuery constructor.
     *
     * @param  DB  $db
     * @param  bool  $isAdmin
     * @param  Filesystem  $storage
     */
    public function __construct(DB $db, Filesystem $storage, bool $isAdmin)
    {
        $this->db = $db;
        $this->isAdmin = $isAdmin;
        $this->storage = $storage;
    }

    /**
     * @param  DB  $db
     * @param  bool  $isAdmin
     * @param  Filesystem  $storage
     * @return MediaRepository
     */
    public static function make(DB $db, Filesystem $storage, bool $isAdmin)
    {
        return new self($db, $storage, $isAdmin);
    }

    /**
     * @param $id
     *
     * @return $this
     */
    public function withUserId($id): MediaRepository
    {
        $this->userId = $id;

        return $this;
    }

    /**
     * @param  string|null  $type
     * @param  string  $mode
     *
     * @return $this
     */
    public function orderBy(string $type = null, $mode = 'ASC'): MediaRepository
    {
        $this->orderBy = $type ?? self::ORDER_TIME;
        $this->orderMode = (strtoupper($mode) === 'ASC') ? 'ASC' : 'DESC';

        return $this;
    }

    /**
     * @param  string|null  $text
     *
     * @return $this
     */
    public function search(?string $text): MediaRepository
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @param $tagId
     * @return $this
     */
    public function filterByTag($tagId): MediaRepository
    {
        if ($tagId !== null) {
            $this->tagId = (int) $tagId;
        }

        return $this;
    }


    /**
     * @param  int  $page
     * @return $this
     */
    public function run(int $page): MediaRepository
    {
        if ($this->orderBy == self::ORDER_SIZE) {
            $this->runWithFileSort($page);
        } else {
            $this->runWithDbSort($page);
        }

        return $this;
    }

    /**
     * @param  int  $page
     * @return $this
     */
    public function runWithDbSort(int $page): MediaRepository
    {
        $params = [];
        if ($this->isAdmin) {
            [$queryMedia, $queryPages] = $this->buildAdminQueries();
            $constPage = self::PER_PAGE_ADMIN;
        } else {
            [$queryMedia, $queryPages] = $this->buildUserQueries();
            $params[] = $this->userId;
            $constPage = self::PER_PAGE;
        }

        if ($this->text !== null) {
            $params[] = '%'.htmlentities($this->text).'%';
        }

        $queryMedia .= $this->buildOrderBy().' LIMIT ? OFFSET ?';

        $this->media = $this->db->query($queryMedia, array_merge($params, [$constPage, $page * $constPage]))->fetchAll();
        $this->pages = $this->db->query($queryPages, $params)->fetch()->count / $constPage;

        $tags = $this->getTags(array_column($this->media, 'id'));

        foreach ($this->media as $media) {
            try {
                $media->size = humanFileSize($this->storage->getSize($media->storage_path));
                $media->mimetype = $this->storage->getMimetype($media->storage_path);
            } catch (FileNotFoundException $e) {
                $media->size = null;
                $media->mimetype = null;
            }
            $media->extension = pathinfo($media->filename, PATHINFO_EXTENSION);
            if (array_key_exists($media->id, $tags)) {
                $media->tags = $tags[$media->id];
            } else {
                $media->tags = [];
            }
        }

        return $this;
    }

    /**
     * @param  int  $page
     * @return $this
     */
    public function runWithFileSort(int $page): MediaRepository
    {
        $this->storage->addPlugin(new ListWith());

        if ($this->isAdmin) {
            $files = $this->storage->listWith(['size', 'mimetype'], '/', true);
            $offset = $page * self::PER_PAGE_ADMIN;
            $limit = self::PER_PAGE_ADMIN;
        } else {
            $userCode = $this->db->query('SELECT `user_code` FROM `users` WHERE `id` = ?', $this->userId)->fetch()->user_code;
            $files = $this->storage->listWith(['size', 'mimetype'], $userCode);
            $offset = $page * self::PER_PAGE;
            $limit = self::PER_PAGE;
        }

        $files = array_filter($files, function ($file) {
            return $file['type'] !== 'dir';
        });

        array_multisort(array_column($files, 'size'), $this->buildOrderBy(), SORT_NUMERIC, $files);

        $params = [];
        $queryPagesParams = [];
        $filterByPaths = false;

        if ($this->text !== null) {
            if ($this->isAdmin) {
                [$queryMedia, $queryPages] = $this->buildAdminQueries();
            } else {
                [$queryMedia, $queryPages] = $this->buildUserQueries();
                $params[] = $this->userId;
            }

            $params[] = '%'.htmlentities($this->text).'%';
            $queryPagesParams = $params;
            $paths = array_column($files, 'path');
        } elseif ($this->tagId !== null) {
            if ($this->isAdmin) {
                [, $queryPages] = $this->buildAdminQueries();
            } else {
                [, $queryPages] = $this->buildUserQueries();
                $queryPagesParams[] = $this->userId;
            }

            $paths = array_column($files, 'path');
            $ids = $this->getMediaIdsByTagId($this->tagId);
            $queryMedia = 'SELECT `uploads`.*, `users`.`user_code`, `users`.`username` FROM `uploads` LEFT JOIN `users` ON `uploads`.`user_id` = `users`.`id` WHERE `uploads`.`storage_path` IN ('.$this->buildPlaceholders($paths).') AND `uploads`.`id` IN ('.$this->buildPlaceholders($ids).')';
            $params = array_merge($params, $paths, $ids);
            $filterByPaths = true;
        } else {
            if ($this->isAdmin) {
                [, $queryPages] = $this->buildAdminQueries();
            } else {
                [, $queryPages] = $this->buildUserQueries();
                $queryPagesParams[] = $this->userId;
            }

            $files = array_slice($files, $offset, $limit, true);
            $paths = array_column($files, 'path');
            $queryMedia = 'SELECT `uploads`.*, `users`.`user_code`, `users`.`username` FROM `uploads` LEFT JOIN `users` ON `uploads`.`user_id` = `users`.`id` WHERE `uploads`.`storage_path` IN ('.$this->buildPlaceholders($paths).')';
            $params = array_merge($params, $paths);
            $filterByPaths = true;
        }

        $medias = [];
        if (!$filterByPaths || count($paths) > 0) {
            $medias = $this->db->query($queryMedia, $params)->fetchAll();
        }

        $paths = array_flip($paths);
        foreach ($medias as $media) {
            $paths[$media->storage_path] = $media;
        }

        $tags = $this->getTags(array_column($medias, 'id'));

        $this->media = [];
        foreach ($files as $file) {
            $media = $paths[$file['path']];
            if (is_object($media)) {
                $media->size = humanFileSize($file['size']);
                $media->extension = $file['extension'];
                $media->mimetype = $file['mimetype'];
                $this->media[] = $media;
                if (array_key_exists($media->id, $tags)) {
                    $media->tags = $tags[$media->id];
                } else {
                    $media->tags = [];
                }
            }
        }

        $this->pages = $this->db->query($queryPages, $queryPagesParams)->fetch()->count / $limit;

        if ($this->text !== null || $this->tagId !== null) {
            $this->media = array_slice($this->media, $offset, $limit, true);
        }

        return $this;
    }

    protected function buildAdminQueries()
    {
        $queryPages = 'SELECT COUNT(*) AS `count` FROM `uploads`';
        $queryMedia = 'SELECT `uploads`.*, `users`.`user_code`, `users`.`username` FROM `uploads` LEFT JOIN `users` ON `uploads`.`user_id` = `users`.`id`';

        if ($this->text !== null || $this->tagId !== null) {
            $queryMedia .= ' WHERE';
            $queryPages .= ' WHERE';
        }

        if ($this->text !== null) {
            $queryMedia .= ' `uploads`.`filename` LIKE ?';
            $queryPages .= ' `filename` LIKE ?';
        }

        if ($this->tagId !== null) {
            if ($this->text !== null) {
                $queryMedia .= ' AND';
                $queryPages .= ' AND';
            }

            $ids = $this->getMediaIdsByTagId($this->tagId);
            $queryMedia .= ' `uploads`.`id` IN ('.implode(',', $ids).')';
            $queryPages .= ' `uploads`.`id` IN ('.implode(',', $ids).')';
        }

        return [$queryMedia, $queryPages];
    }

    protected function buildUserQueries()
    {
        $queryPages = 'SELECT COUNT(*) AS `count` FROM `uploads` WHERE `user_id` = ?';
        $queryMedia = 'SELECT `uploads`.*,`users`.`user_code`, `users`.`username` FROM `uploads` INNER JOIN `users` ON `uploads`.`user_id` = `users`.`id` WHERE `user_id` = ?';

        if ($this->text !== null) {
            $queryMedia .= ' AND `uploads`.`filename` LIKE ? ';
            $queryPages .= ' AND `filename` LIKE ?';
        }

        if ($this->tagId !== null) {
            $ids = $this->getMediaIdsByTagId($this->tagId);
            $queryMedia .= ' AND `uploads`.`id` IN ('.implode(',', $ids).')';
            $queryPages .= ' AND `uploads`.`id` IN ('.implode(',', $ids).')';
        }

        return [$queryMedia, $queryPages];
    }

    protected function buildOrderBy()
    {
        switch ($this->orderBy) {
            case self::ORDER_NAME:
                return ' ORDER BY `filename` '.$this->orderMode;
            case self::ORDER_TIME:
                return ' ORDER BY `timestamp` '.$this->orderMode;
            case self::ORDER_SIZE:
                return ($this->orderMode === 'ASC') ? SORT_ASC : SORT_DESC;
            default:
                return '';
        }
    }

    /**
     * Build the placeholders of an IN clause, so that the values can be bound
     * as query parameters instead of being concatenated into the statement.
     *
     * @param  array  $values
     * @return string
     */
    protected function buildPlaceholders(array $values): string
    {
        return implode(',', array_fill(0, count($values), '?'));
    }

    /**
     * @param  array  $mediaIds
     * @return array
     */
    protected function getTags(array $mediaIds)
    {
        if (count($mediaIds) === 0) {
            return [];
        }

        $allTags = $this->db->query('SELECT `uploads_tags`.`upload_id`,`tags`.`id`, `tags`.`name` FROM `uploads_tags` INNER JOIN `tags` ON `uploads_tags`.`tag_id` = `tags`.`id` WHERE `uploads_tags`.`upload_id` IN ('.$this->buildPlaceholders($mediaIds).') ORDER BY `tags`.`timestamp`', $mediaIds)->fetchAll();
        $tags = [];
        foreach ($allTags as $tag) {
            $tags[$tag->upload_id][$tag->id] = $tag->name;
        }
        return $tags;
    }

    /**
     * @param $tagId
     * @return array
     */
    protected function getMediaIdsByTagId($tagId)
    {
        $mediaIds = $this->db->query('SELECT `upload_id` FROM `uploads_tags` WHERE `tag_id` = ?', $tagId)->fetchAll();
        $ids = [-1];
        foreach ($mediaIds as $pivot) {
            $ids[] = $pivot->upload_id;
        }
        return $ids;
    }

    /**
     * @return mixed
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * @return mixed
     */
    public function getPages()
    {
        return $this->pages;
    }
}
