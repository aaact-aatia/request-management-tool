const assert = require('node:assert/strict');
const { chromium } = require('playwright-core');

const baseUrl = (process.env.RMT_BROWSER_BASE_URL || 'http://localhost:8081').replace(/\/$/, '');
const chromePath = process.env.RMT_CHROME_PATH || undefined;

async function submitAndWait(page, button) {
    const [response] = await Promise.all([
        page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
        button.click({ noWaitAfter: true }),
    ]);

    assert.ok(response, 'Form submission must produce a document response');
    assert.ok(response.status() < 400, `Form submission returned HTTP ${response.status()}`);
}

(async () => {
    const browser = await chromium.launch({
        headless: true,
        ...(chromePath ? { executablePath: chromePath } : {}),
    });

    try {
        // JavaScript is disabled before the first openrequest.php navigation.
        const context = await browser.newContext({ javaScriptEnabled: false });
        const page = await context.newPage();
        page.setDefaultTimeout(10000);

        await page.goto(`${baseUrl}/signin.php?lang=en`);
        await page.locator('#token1').fill('superadmin@example.com');
        await page.locator('#token2').fill('password');
        await submitAndWait(page, page.locator('#login button[type="submit"]'));

        await page.goto(`${baseUrl}/openrequest.php?lang=en`);
        assert.equal(new URL(page.url()).searchParams.has('run'), false,
            'Initial page must not contain a pre-created intake run');
        assert.equal(await page.locator('#intake-workflow').getAttribute('data-intake-run-token'), '',
            'Initial page must not expose a run token');

        await page.locator('#service_stream').selectOption('catalogue_8');
        await submitAndWait(
            page,
            page.locator('button[name="cascade_action"][value="select_catalogue"]')
        );

        assert.equal(new URL(page.url()).searchParams.has('run'), false,
            'Catalogue submission must not create an intake run');
        assert.equal(await page.locator('#service_stream').inputValue(), 'catalogue_8');
        assert.equal(await page.locator('#serviceid').getAttribute('autofocus'), '',
            'Server-rendered service select should receive focus after catalogue submission');
        assert.equal(await page.locator('#serviceid').evaluate(element => element === document.activeElement), true,
            'Server-rendered service select must hold focus after catalogue submission');

        await page.locator('#serviceid').selectOption('28');
        await submitAndWait(
            page,
            page.locator('button[name="cascade_action"][value="select_service"]')
        );

        assert.equal(new URL(page.url()).searchParams.has('run'), false,
            'Service submission must not create an intake run');
        assert.equal(await page.locator('#intake-workflow').getAttribute('data-intake-run-token'), '',
            'Run token must remain empty until explicit flow start');
        const startButton = page.getByRole('button', { name: 'Start intake questionnaire' });
        await startButton.waitFor();

        await submitAndWait(page, startButton);
        const runToken = new URL(page.url()).searchParams.get('run');
        assert.match(runToken || '', /^[0-9a-f]{32}$/,
            'Explicit server-rendered start must create the intake run');
        assert.equal(await page.locator('#intake-workflow > .intake-path-item').count(), 1);
        assert.equal(await page.locator('#intake-workflow .intake-question-select').evaluate(
            element => element === document.activeElement
        ), true, 'First intake question must hold focus after Start');

        let currentStep = page.locator('#intake-workflow > .intake-path-item').last();
        await currentStep.locator('.intake-question-select').selectOption({ label: 'Yes' });
        await submitAndWait(page, currentStep.locator('button[type="submit"]'));

        assert.equal(new URL(page.url()).searchParams.get('run'), runToken);
        assert.equal(await page.locator('#intake-workflow > .intake-path-item').count(), 2);
        currentStep = page.locator('#intake-workflow > .intake-path-item').last();
        assert.equal(await currentStep.locator('.intake-question-select').evaluate(
            element => element === document.activeElement
        ), true, 'Next intake question must hold focus after Continue');
        await currentStep.locator('.intake-question-select').selectOption({ label: 'Yes' });
        await submitAndWait(page, currentStep.locator('button[type="submit"]'));

        const destination = page.locator('#intake-workflow .intake-destination');
        await destination.waitFor();
        assert.equal(await page.locator('#intake-workflow > .intake-path-item').count(), 3);
        assert.equal(await destination.locator('input[name="intake_run_token"]').inputValue(), runToken);
        assert.equal(await destination.evaluate(element => element === document.activeElement), true,
            'Destination summary must hold focus after the final answer');

        const destinationRequestPromise = page.waitForRequest(request =>
            request.method() === 'POST' && request.url().includes('/openrequest2.php?lang=en')
        );
        await submitAndWait(
            page,
            destination.getByRole('button', { name: 'Continue to request form' })
        );
        const destinationRequest = await destinationRequestPromise;
        const destinationPostData = destinationRequest.postData() || '';
        assert.match(destinationPostData, new RegExp(`(?:^|&)intake_run_token=${runToken}(?:&|$)`),
            'Destination form must submit the validated intake run token');
        assert.match(page.url(), /\/openrequest2\.php\?lang=en$/,
            'Completed intake must continue to the request form');

        console.log('PASS: no-JavaScript intake starts from server-rendered catalogue/service submissions and completes');
        await context.close();
    } finally {
        await browser.close();
    }
})().catch(error => {
    console.error(error && error.stack ? error.stack : error);
    process.exit(1);
});