// @ts-check

// https://stackoverflow.com/a/69219159/54829

/**
 * @template T
 * @param {number} maxConcurrency 
 * @param {IterableIterator<() => Promise<T>>} taskIterator 
 * @returns AsyncGenerator<T>
 */
export async function* runTasks(maxConcurrency, taskIterator) {
    async function* createWorkerIterator() {
        // Each AsyncGenerator that this function* creates is a worker,
        // polling for tasks from the shared taskIterator. Sharing the
        // taskIterator ensures that each worker gets unique tasks.
        for (const task of taskIterator) yield await task();
    }

    const asyncIterators = new Array(maxConcurrency);
    for (let i = 0; i < maxConcurrency; i++) {
        asyncIterators[i] = createWorkerIterator();
    }
    yield* raceAsyncIterators(asyncIterators);
}

/**
 * @template T
 * @param {AsyncIterator<T>[]} asyncIterators
 * @returns AsyncGenerator<T>
 */
async function* raceAsyncIterators(asyncIterators) {
    /** @param {AsyncIterator<T>} iterator */
    async function nextResultWithItsIterator(iterator) {
        return { result: await iterator.next(), iterator: iterator };
    }

    /** @type {Map<AsyncIterator<T>, Promise<{result: IteratorResult<T>, iterator: AsyncIterator<T>}>>} */
    const promises = new Map();
    for (const iterator of asyncIterators) {
        promises.set(iterator, nextResultWithItsIterator(iterator));
    }
    while (promises.size) {
        const { result, iterator } = await Promise.race(promises.values());
        if (result.done) {
            promises.delete(iterator);
        } else {
            promises.set(iterator, nextResultWithItsIterator(iterator));
            yield result.value;
        }
    }
}