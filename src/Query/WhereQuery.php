<?php
/*
* File:     Query.php
* Category: -
* Author:   M. Goldenbaum
* Created:  21.07.18 18:54
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP\Query;

use Closure;
use Illuminate\Support\Str;
use Webklex\PHPIMAP\Exceptions\InvalidWhereQueryCriteriaException;
use Webklex\PHPIMAP\Exceptions\MethodNotFoundException;
use Webklex\PHPIMAP\Exceptions\MessageSearchValidationException;

/**
 * Class WhereQuery
 *
 * @package Webklex\PHPIMAP\Query
 *
 * @method WhereQuery all()
 * @method WhereQuery answered()
 * @method WhereQuery deleted()
 * @method WhereQuery new()
 * @method WhereQuery old()
 * @method WhereQuery recent()
 * @method WhereQuery seen()
 * @method WhereQuery unanswered()
 * @method WhereQuery undeleted()
 * @method WhereQuery unflagged()
 * @method WhereQuery unseen()
 * @method WhereQuery not()
 * @method WhereQuery unkeyword($value)
 * @method WhereQuery to($value)
 * @method WhereQuery text($value)
 * @method WhereQuery subject($value)
 * @method WhereQuery since($date)
 * @method WhereQuery on($date)
 * @method WhereQuery keyword($value)
 * @method WhereQuery from($value)
 * @method WhereQuery flagged()
 * @method WhereQuery cc($value)
 * @method WhereQuery body($value)
 * @method WhereQuery before($date)
 * @method WhereQuery bcc($value)
 * @method WhereQuery inReplyTo($value)
 * @method WhereQuery messageId($value)
 *
 * @mixin Query
 */
class WhereQuery extends Query {

    /**
     * @var array $available_criteria
     */
    protected $available_criteria = [
        'OR', 'AND',
        'ALL', 'ANSWERED', 'BCC', 'BEFORE', 'BODY', 'CC', 'DELETED', 'FLAGGED', 'FROM', 'KEYWORD',
        'NEW', 'NOT', 'OLD', 'ON', 'RECENT', 'SEEN', 'SINCE', 'SUBJECT', 'TEXT', 'TO',
        'UNANSWERED', 'UNDELETED', 'UNFLAGGED', 'UNKEYWORD', 'UNSEEN', 'UID'
    ];

    /**
     * Magic method in order to allow alias usage of all "where" methods in an optional connection with "NOT"
     * @param string $name
     * @param array|null $arguments
     *
     * @return mixed
     * @throws InvalidWhereQueryCriteriaException
     * @throws MethodNotFoundException
     */
    public function __call(string $name, $arguments) {
        $that = $this;

        $name = Str::camel($name);

        if (strtolower(substr($name, 0, 3)) === 'not') {
            $that = $that->whereNot();
            $name = substr($name, 3);
        }

        if (strpos(strtolower($name), "where") === false) {
            $method = 'where' . ucfirst($name);
        } else {
            $method = lcfirst($name);
        }

        if (method_exists($this, $method) === true) {
            return call_user_func_array([$that, $method], $arguments);
        }

        throw new MethodNotFoundException("Method " . self::class . '::' . $method . '() is not supported');
    }

    /**
     * Validate a given criteria
     * @param $criteria
     *
     * @return string
     * @throws InvalidWhereQueryCriteriaException
     */
    protected function validate_criteria($criteria): string {
        $command = strtoupper($criteria);
        if (substr($command, 0, 7) === "CUSTOM ") {
            return substr($criteria, 7);
        }
        if (in_array($command, $this->available_criteria) === false) {
            throw new InvalidWhereQueryCriteriaException("Invalid imap search criteria: $command");
        }

        return $criteria;
    }

    /**
     * Register search parameters
     * @param mixed $criteria
     * @param null $value
     *
     * @return $this
     * @throws InvalidWhereQueryCriteriaException
     *
     * Examples:
     * $query->from("someone@email.tld")->seen();
     * $query->whereFrom("someone@email.tld")->whereSeen();
     * $query->where([["FROM" => "someone@email.tld"], ["SEEN"]]);
     * $query->where(["FROM" => "someone@email.tld"])->where(["SEEN"]);
     * $query->where(["FROM" => "someone@email.tld", "SEEN"]);
     * $query->where("FROM", "someone@email.tld")->where("SEEN");
     */
    public function where($criteria, $value = null): self {
        if (is_array($criteria)) {
            foreach ($criteria as $key => $value) {
                if (is_numeric($key)) {
                    $this->where($value);
                }else{
                    $this->where($key, $value);
                }
            }
        } else {
            $this->push_search_criteria($criteria, $value);
        }

        return $this;
    }

    /**
     * Push a given search criteria and value pair to the search query
     * @param $criteria string
     * @param $value mixed
     *
     * @throws InvalidWhereQueryCriteriaException
     */
    protected function push_search_criteria(string $criteria, $value): void{
        $criteria = $this->validate_criteria($criteria);
        $value = $this->parse_value($value);

        if ($value === null || $value === '') {
            $this->query->push([$criteria]);
        } else {
            $this->query->push([$criteria, $value]);
        }
    }

    /**
     * @param Closure|null $closure
     *
     * @return $this
     */
    public function orWhere(Closure $closure = null): self {
        $this->query->push(['OR']);
        if ($closure !== null) $closure($this);

        return $this;
    }

    /**
     * @param Closure|null $closure
     *
     * @return $this
     */
    public function andWhere(Closure $closure = null): self {
        $this->query->push(['AND']);
        if ($closure !== null) $closure($this);

        return $this;
    }

    /**
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereAll(): self {
        return $this->where('ALL');
    }

    /**
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereAnswered(): self {
        return $this->where('ANSWERED');
    }

    /**
     * @param string $value
     *
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereBcc(string $value): self {
        return $this->where('BCC', $value);
    }

    /**
     * @param mixed $value
     * @throws InvalidWhereQueryCriteriaException
     * @throws MessageSearchValidationException
     */
    public function whereBefore($value): self {
        $date = $this->parse_date($value);
        return $this->where('BEFORE', $date);
    }

    /**
     * @param string $value
     *
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereBody(string $value): self {
        return $this->where('BODY', $value);
    }

    /**
     * @param string $value
     *
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereCc(string $value): self {
        return $this->where('CC', $value);
    }

    /**
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereDeleted(): self {
        return $this->where('DELETED');
    }

    /**
     * @param string $value
     *
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereFlagged(string $value): self {
        return $this->where('FLAGGED', $value);
    }

    /**
     * @param string $value
     *
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereFrom(string $value): self {
        return $this->where('FROM', $value);
    }

    /**
     * @param string $value
     *
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereKeyword(string $value): self {
        return $this->where('KEYWORD', $value);
    }

    /**
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereNew(): self {
        return $this->where('NEW');
    }

    /**
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereNot(): self {
        return $this->where('NOT');
    }

    /**
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereOld(): self {
        return $this->where('OLD');
    }

    /**
     * @param mixed $value
     *
     * @throws MessageSearchValidationException
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereOn($value): self {
        $date = $this->parse_date($value);
        return $this->where('ON', $date);
    }

    /**
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereRecent(): self {
        return $this->where('RECENT');
    }

    /**
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereSeen(): self {
        return $this->where('SEEN');
    }

    /**
     * @param mixed $value
     *
     * @throws MessageSearchValidationException
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereSince($value): self {
        $date = $this->parse_date($value);
        return $this->where('SINCE', $date);
    }

    /**
     * @param string $value
     *
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereSubject(string $value): self {
        return $this->where('SUBJECT', $value);
    }

    /**
     * @param string $value
     *
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereText(string $value): self {
        return $this->where('TEXT', $value);
    }

    /**
     * @param string $value
     *
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereTo(string $value): self {
        return $this->where('TO', $value);
    }

    /**
     * @param string $value
     *
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereUnkeyword(string $value): self {
        return $this->where('UNKEYWORD', $value);
    }

    /**
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereUnanswered(): self {
        return $this->where('UNANSWERED');
    }

    /**
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereUndeleted(): self {
        return $this->where('UNDELETED');
    }

    /**
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereUnflagged(): self {
        return $this->where('UNFLAGGED');
    }

    /**
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereUnseen(): self {
        return $this->where('UNSEEN');
    }

    /**
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereNoXSpam(): self {
        return $this->where("CUSTOM X-Spam-Flag NO");
    }

    /**
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereIsXSpam(): self {
        return $this->where("CUSTOM X-Spam-Flag YES");
    }

    /**
     * Search for a specific header value
     * @param $header
     * @param $value
     *
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereHeader($header, $value): self {
        return $this->where("CUSTOM HEADER $header $value");
    }

    /**
     * Search for a specific message id
     * @param $messageId
     *
     * @return WhereQuery
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereMessageId($messageId): WhereQuery {
        return $this->whereHeader("Message-ID", $messageId);
    }

    /**
     * Search for a specific message id
     * @param $messageId
     *
     * @return WhereQuery
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereInReplyTo($messageId): WhereQuery {
        return $this->whereHeader("In-Reply-To", $messageId);
    }

    /**
     * @param $country_code
     *
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereLanguage($country_code): self {
        return $this->where("Content-Language $country_code");
    }

    /**
     * Get message be it UID.
     *
     * @param int|string $uid
     *
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereUid($uid): self {
        return $this->where('UID', $uid);
    }

    /**
     * Get messages by their UIDs.
     *
     * @param array<int, int> $uids
     *
     * @throws InvalidWhereQueryCriteriaException
     */
    public function whereUidIn(array $uids): self {
        $uids = implode(',', $uids);
        return $this->where('UID', $uids);
    }

    /**
     * Apply the callback if the given "value" is truthy.
     * copied from @url https://github.com/laravel/framework/blob/8.x/src/Illuminate/Support/Traits/Conditionable.php
     *
     * @param mixed $value
     * @param callable $callback
     * @param callable|null $default
     * @return $this|mixed
     */
    public function when($value, callable $callback, $default = null) {
        if ($value) {
            return $callback($this, $value) ?: $this;
        }
        if ($default) {
            return $default($this, $value) ?: $this;
        }

        return $this;
    }

    /**
     * Apply the callback if the given "value" is falsy.
     * copied from @url https://github.com/laravel/framework/blob/8.x/src/Illuminate/Support/Traits/Conditionable.php
     *
     * @param mixed $value
     * @param callable $callback
     * @param callable|null $default
     * @return $this|mixed
     */
    public function unless($value, callable $callback, $default = null) {
        if (!$value) {
            return $callback($this, $value) ?: $this;
        }
        if ($default) {
            return $default($this, $value) ?: $this;
        }

        return $this;
    }
}