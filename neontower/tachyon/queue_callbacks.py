from Queue import Queue

from tachyon.emitter_callbacks import EmitterCallbacks

class QueueCallbacks(EmitterCallbacks):
    def __init__(self):
        self.queue = Queue()

    def emitter(self, data):
        self.queue.put(data)
