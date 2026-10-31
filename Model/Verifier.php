<?php

declare(strict_types=1);

namespace Hostily\Captcha\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

/**
 * Prueft ein Token beim Dienst (POST <Dienst>/api/v1/pruefen, Protokoll: dienst/docs/protokoll.md).
 *
 * Ergebnis: ['status' => 'ok'|'rejected'|'error', 'reasons' => string[]]. 'error' gibt es nur bei
 * "Bei Ausfall: blockieren"; bei "durchlassen" (Vorgabe) ist ein Ausfall oder ein Konfigurationsfehler 'ok'
 * mit dem Grund 'service' (und einem Eintrag im Protokoll). Das Secret steht in keinem Protokolleintrag.
 */
class Verifier
{
    public const VERSION = '0.1.2';

    private const CONFIG_ERRORS = ['invalid-input-secret', 'missing-input-secret', 'bad-request'];
    /** Felder, die nie an den Dienst gehen (Kennwoerter, Tokens, technische Felder) */
    private const SKIP_FIELD = '/pass|pwd|token|captcha|form_key|secret|key$|^(cc_|payment)/i';

    /** @var array<string, array{status: string, reasons: array<int, string>}> Ergebnisse dieser Anfrage je Token */
    private array $verified = [];

    public function __construct(
        private readonly CurlFactory $curlFactory,
        private readonly Json $json,
        private readonly RequestInterface $request,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return array{status: string, reasons: array<int, string>}
     */
    public function verify(string $token, ValidationConfig $config): array
    {
        // Ein leeres Token wird trotzdem gesendet: ist der Dienst erreichbar, ist die Einsendung abgelehnt; ist er es
        // nicht, gilt "Bei Ausfall" (sonst sperrt ein nicht ladendes Widget alle Besucher aus).
        if ($token !== '' && isset($this->verified[$token])) {
            return $this->verified[$token];
        }
        $result = $this->ask($token, $config);
        if ($token !== '') {
            // Ein Token gilt nur einmal, derselbe Aufruf kann aber mehrfach pruefen. Begrenzt fuer langlebige Prozesse.
            if (\count($this->verified) >= 50) {
                $this->verified = [];
            }
            $this->verified[$token] = $result;
        }

        return $result;
    }

    /**
     * @return array{status: string, reasons: array<int, string>}
     */
    private function ask(string $token, ValidationConfig $config): array
    {
        if ($config->getServiceUrl() === '' || $config->getPrivateKey() === '') {
            return $this->failure('not configured (service URL or secret missing)', $config);
        }
        $body = [
            'secret' => $config->getPrivateKey(),
            'response' => $token,
            'remoteip' => $config->getRemoteIp(),
        ];
        $fields = $this->formFields();
        if ($fields !== []) {
            $body['formular'] = $fields;
        }

        try {
            $curl = $this->curlFactory->create();
            $curl->setTimeout(8);
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 5);
            $curl->setOption(CURLOPT_FOLLOWLOCATION, false);
            $curl->setOption(CURLOPT_USERAGENT, 'hostily-captcha-magento2/' . self::VERSION);
            $curl->addHeader('Content-Type', 'application/json');
            $curl->addHeader('Accept', 'application/json');
            $curl->post($config->getServiceUrl() . '/api/v1/pruefen', $this->json->serialize($body));
            $code = (int) $curl->getStatus();
            $data = json_decode((string) $curl->getBody(), true);
        } catch (\Throwable $e) {
            return $this->failure('service unreachable: ' . $e->getMessage(), $config);
        }

        if ($code !== 200 || !\is_array($data) || !isset($data['success'])) {
            return $this->failure('unexpected answer from service (HTTP ' . $code . ')', $config);
        }
        $codes = [];
        if (isset($data['error-codes']) && \is_array($data['error-codes'])) {
            foreach ($data['error-codes'] as $c) {
                if (\is_string($c)) {
                    $codes[] = (string) preg_replace('/[^a-z0-9_-]/i', '', $c);
                }
            }
        }
        if (array_intersect($codes, self::CONFIG_ERRORS) !== []) {
            return $this->failure('service rejected the configuration: ' . implode(',', $codes), $config);
        }
        if ($data['success'] === true) {
            return ['status' => 'ok', 'reasons' => []];
        }
        $reasons = [];
        if (isset($data['gruende']) && \is_array($data['gruende'])) {
            foreach ($data['gruende'] as $r) {
                if (\is_string($r)) {
                    $reasons[] = substr((string) preg_replace('/[^a-z0-9_:.-]/i', '', $r), 0, 60);
                }
            }
        }

        return ['status' => 'rejected', 'reasons' => $reasons];
    }

    /**
     * Dienst- oder Konfigurationsproblem: durchlassen (Vorgabe) oder blockieren, immer protokollieren.
     *
     * @return array{status: string, reasons: array<int, string>}
     */
    private function failure(string $why, ValidationConfig $config): array
    {
        $this->logger->error('hostily Captcha: ' . substr($why, 0, 300));
        if ($config->blocksOnError()) {
            return ['status' => 'error', 'reasons' => ['service']];
        }

        return ['status' => 'ok', 'reasons' => ['service']];
    }

    /**
     * Textfelder des abgesendeten Formulars fuer die Inhaltspruefung des Dienstes (nur normale Formular-Anfragen,
     * keine Kennwoerter, Tokens oder technischen Felder; die REST-Schnittstelle der Kasse liefert keine).
     *
     * @return array<string, string>
     */
    private function formFields(): array
    {
        if (!$this->request instanceof \Magento\Framework\App\Request\Http || !$this->request->isPost()) {
            return [];
        }
        $out = [];
        foreach ((array) $this->request->getPostValue() as $name => $value) {
            if (!\is_string($name) || !\is_string($value) || $value === '' || preg_match(self::SKIP_FIELD, $name) === 1) {
                continue;
            }
            $out[substr($name, 0, 60)] = mb_substr($value, 0, 2000);
            if (\count($out) >= 30) {
                break;
            }
        }

        return $out;
    }
}
