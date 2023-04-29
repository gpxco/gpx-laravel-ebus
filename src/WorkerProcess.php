<?php

namespace GPX\EventBus;

use GPX\EventBus\Contracts\Subscriber;
use DanikDantist\QueueWrapper\Manager;

class WorkerProcess
{
    protected string $consumerName = '';
    
    /** @var array<Subscriber>  */
    protected array $subscribers = [];

   
    public function __construct(protected Manager $queueManager, protected Worker\WorkerOptions $workerOptions)
    {
        $this->consumerName = $this->workerOptions->serviceConsumerName;
    }

    /**
     * @param array<string> $subscribers - list of className which implement Subscriber interface
     * @return $this
     */
    public function setSubscribers(array $subscribers): static
    {
        $this->subscribers = $subscribers;

        return $this;
    }

    /**
     * @return $this
     */
    public function setConsumerName(string $consumerName): static
    {
        $this->consumerName = $consumerName;

        return $this;
    }

    public function run(): int
    {
        $config = $this->queueManager->getConfig();
        $config->setGroup($this->consumerName);

        /** @var Subscriber[] $subscribers */
        $subscribers = [];
        foreach ($this->subscribers as $subscriber) {
            $interfaces = class_implements($subscriber);

            if ($interfaces && in_array(Subscriber::class, $interfaces)) {
                $subscribers[] = app($subscriber);
            } else {
                \Log::info("Subscriber: {$subscriber}, doesn't implement ".Subscriber::class." interface");
            }
        }

        $topicList = [];
        foreach ($subscribers as $subscriber) {
            $subscription = $subscriber->subscribedTo();
            $receiver = new Worker\Receiver($subscriber);
            $topicList[] = $subscription->getQueueName();
            $this->queueManager->addReceiver($receiver);
        }
        foreach (array_unique($topicList) as $topic) {
            $config->addTopic($topic);
        }

        if ($subscribers) {
            $this->queueManager->listenMessage();

            return 0;
        } else {
            return 1;
        }
    }
}
