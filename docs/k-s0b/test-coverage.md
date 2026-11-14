# Test Coverage Analysis

**Baseline:** `543329ed84a2148308e0cc455ac7e2fe27cf35f4`

## Method

Test files were identified by listing all `.php` files under `tests/` subdirectories of each Core component.
Each test file was inspected to identify the class under test and the behaviours verified.

## Test Coverage by Component

### CORE-02 — Reactive DI Container

**Test files:** 18

| Test File | Class Under Test | Behaviours |
|---|---|---|
| `tests/Fixtures/A.php` | A |  |
| `tests/Fixtures/AbstractService.php` | AbstractService |  |
| `tests/Fixtures/B.php` | B |  |
| `tests/Fixtures/ClassString.php` | ClassString |  |
| `tests/Fixtures/ClosureConcrete.php` | ClosureConcrete |  |
| `tests/Fixtures/NoConstructor.php` | NoConstructor |  |
| `tests/Fixtures/ObjectConcrete.php` | ObjectConcrete |  |
| `tests/Fixtures/Service.php` | Service |  |
| `tests/Fixtures/Unresolvable.php` | has |  |
| `tests/Fixtures/WithDefaults.php` | WithDefaults |  |
| `tests/Fixtures/WithParams.php` | WithParams |  |
| `tests/Performance/ResolutionBenchTest.php` | ResolutionBenchTest | testResolutionDepth1, testResolutionDepth5, testCompileIdempotency, testFinallyCleanupAfterCycle |
| `tests/Psr11Conformance/Psr11ConformanceTest.php` | Psr11ConformanceTest | testContainerImplementsPsr11Interface, testGetReturnsRegisteredEntry, testGetThrowsNotFoundExceptionImplementingPsr11Interface, testHasReturnsTrueForKnownIds, testHasReturnsFalseForUnknownIds (+8 more) |
| `tests/Unit/AutowiringTest.php` | AutowiringTest | testAutowiresClosureConcrete, testAutowiresObjectConcrete, testAutowiresClassStringConcrete, testAutowiresDefaultScalar, testAutowiresServiceWithNoConstructor (+5 more) |
| `tests/Unit/CircularDependencyTest.php` | CircularDependencyTest | testCycleDetectionThrowsException, testExceptionContainsResolutionChain, testCycleDetectionDoesNotLeakState |
| `tests/Unit/CompileTest.php` | CompileTest | testCompileIdempotent, testBindAfterCompileThrowsLogicException, testCompileRunsCompilerPasses |
| `tests/Unit/ContainerTest.php` | ContainerTest | testMakeResolvesBoundService, testMakeResolvesSingleton, testMakeResolvesClosure, testMakeResolvesClassWithNoConstructor, testMakeWithParameters (+30 more) |
| `tests/Unit/PulseShapeCTest.php` | PulseShapeCTest | testObjectValueReturnedUnchanged, testStringValueReturnedLiterally, testIntegerValueReturnedLiterally, testNullValueBoundLiterally, testClosureThrowsContainerException (+5 more) |

### CORE-03 — PSR-14 Event Dispatcher

**Test files:** 7

| Test File | Class Under Test | Behaviours |
|---|---|---|
| `tests/EventDispatcherTest.php` | EventDispatcherTest | testDispatchCallsRegisteredListeners, testDispatchReturnsEventUnchangedWhenNoListeners, testDispatchCallsListenersInPriorityOrder, testDispatchStopsOnStoppableEventPropagation, testDispatchContinuesOnListenerFailure (+9 more) |
| `tests/EventTest.php` | EventTest | testPropagationIsNotStoppedByDefault, testStopPropagationHaltsEvent, testStopPropagationOnStoppableEvent, testStampReturnsFluentInterface, testStampRecordsValue (+9 more) |
| `tests/Fixtures/FailingListener.php` | FailingListener |  |
| `tests/Fixtures/SampleListener.php` | SampleListener |  |
| `tests/Fixtures/TestEvent.php` | TestEvent |  |
| `tests/Fixtures/TestStoppableEvent.php` | TestStoppableEvent |  |
| `tests/ListenerProviderTest.php` | ListenerProviderTest | testAddListenerWithCallableStoresSuccessfully, testAddListenerWithClassNameStoresSuccessfully, testAddListenerThrowsForInvalidEventClass, testAddListenerThrowsForInvalidListenerClass, testAddListenerAcceptsOnlyStringOrCallable (+9 more) |

### CORE-04 — PSR-7 HTTP Message & Factory

**Test files:** 11

| Test File | Class Under Test | Behaviours |
|---|---|---|
| `tests/Immutability/ImmutabilityTest.php` | ImmutabilityTest | testResponseWithMethodsReturnNewInstance, testUriWithMethodsReturnNewInstance, testRequestWithMethodsReturnNewInstance, testServerRequestWithMethodsReturnNewInstance, testServerRequestWithoutAttributeReturnsSameInstanceWhenMissing (+3 more) |
| `tests/Security/HeaderInjectionTest.php` | HeaderInjectionTest | testResponseConstructorRejectsCrlfInName, testResponseConstructorRejectsCrlfInValue, testResponseWithHeaderRejectsCrlfInName, testResponseWithHeaderRejectsCrlfInValue, testResponseWithAddedHeaderRejectsCrlfInName (+11 more) |
| `tests/Unit/MessageFactoryTest.php` | MessageFactoryTest | testCreateRequest, testCreateRequestWithUriObject, testCreateResponse, testCreateResponseDefaultIs200, testCreateServerRequest (+8 more) |
| `tests/Unit/MultiFileUploadTest.php` | MultiFileUploadTest | testSingleFileUpload, testMultiFileUpload, testNestedMultiFileUpload, testMixedSingleAndMultiFile |
| `tests/Unit/RequestTest.php` | RequestTest | testDefaultMethodIsGet, testMethodIsUppercased, testWithMethodReturnsNewInstance, testGetUriReturnsUriObject, testWithUriReturnsNewInstance (+14 more) |
| `tests/Unit/ResponseTest.php` | ResponseTest | testDefaultStatusCodeIs200, testAcceptsMinimumValidStatusCode100, testAcceptsMaximumValidStatusCode599, testRejectsStatusCodeBelow100, testRejectsStatusCodeAbove599 (+32 more) |
| `tests/Unit/ServerRequestFactoryTest.php` | ServerRequestFactoryTest | testCreateServerRequest, testFromGlobalsBuildsBasicRequest, testFromGlobalsDetectsHttps, testFromGlobalsNormalizesHttpHeaders, testFromGlobalsSetsQueryParams (+6 more) |
| `tests/Unit/ServerRequestTest.php` | ServerRequestTest | testGetServerParamsReturnsArray, testWithCookieParamsReturnsNewInstance, testWithQueryParamsReturnsNewInstance, testWithUploadedFilesReturnsNewInstance, testWithParsedBodyReturnsNewInstance (+7 more) |
| `tests/Unit/StreamTest.php` | StreamTest | testConstructFromFilenameCreatesReadableWritableStream, testConstructFromReadOnlyFile, testConstructFromResource, testConstructRejectsNonStringNonResource, testConstructThrowsOnUnopenableFile (+36 more) |
| `tests/Unit/UploadedFileTest.php` | UploadedFileTest | testConstructFromStream, testGetStreamReturnsOriginalStream, testGetStreamThrowsAfterMove, testGetStreamThrowsOnUploadError, testMoveToRejectsEmptyPath (+6 more) |
| `tests/Unit/UriTest.php` | UriTest | testEmptyUriProducesEmptyString, testParsesFullUri, testSchemeIsLowercased, testStandardPort80ReturnsNull, testStandardPort443ReturnsNull (+16 more) |

### CORE-05 — PSR-15 Middleware & Request Handler

**Test files:** 7

| Test File | Class Under Test | Behaviours |
|---|---|---|
| `tests/Exception/ExceptionPropagationTest.php` | ExceptionPropagationTest | testExceptionFromMiddlewarePropagatesUncaught, testExceptionFromFinalHandlerPropagatesUncaught, testExceptionFromInnerMiddlewareReachesOuterMiddleware |
| `tests/Immutability/PipelineImmutabilityTest.php` | PipelineImmutabilityTest | testPipeAfterHandleThrowsLogicException, testSecondHandleDoesNotReFreeze, testCursorResetsBetweenRequests, testFrozenFlagPersistsAcrossRequests |
| `tests/Order/OrderInvariantTest.php` | OrderInvariantTest | testMiddlewareOrderIsBoustrophedon, testFifoOrderWithThreeMiddleware |
| `tests/Unit/CallableMiddlewareAdapterTest.php` | CallableMiddlewareAdapterTest | testProcessInvokesCallable, testProcessPassesRequestAndHandlerToCallable, testProcessReturnsResponseFromCallable |
| `tests/Unit/FinalRequestHandlerTest.php` | FinalRequestHandlerTest | testHandleWithNoRouterThrowsLogicException, testWithRouterReturnsNewInstance, testWithRouterIsImmutable, testHandleRejectsNonCallableControllerMethod, testHandleRejectsControllerResultThatIsNotResponse |
| `tests/Unit/MiddlewarePipelineTest.php` | MiddlewarePipelineTest | testPipeAppendsMiddleware, testHandleAdvancesCursorAndDelegatesToFinalHandler, testFirstHandleFreezesPipeline, testSecondHandleDoesNotRefreeze, testCallableMiddlewareWorks (+7 more) |
| `tests/Unit/MiddlewareResolverTest.php` | MiddlewareResolverTest | testMiddlewareInterfacePassesThrough, testCallableIsWrappedInAdapter, testClassStringResolvedViaContainer, testClassStringResolvingToNonMiddlewareThrowsTypeError, testInvalidTypeThrowsTypeError (+1 more) |

### CORE-06 — Attribute-Based Router

**Test files:** 7

| Test File | Class Under Test | Behaviours |
|---|---|---|
| `tests/Fixtures/Routes/AttributedController.php` | AttributedController |  |
| `tests/Performance/RouterBenchTest.php` | RouterBenchTest | testLargeRouteTableMatchesCorrectly, testLargeRouteTableReturnsNullOnNoMatch |
| `tests/Security/PathTraversalTest.php` | PathTraversalTest | testRoutePatternWithTraversalThrows, testRoutePatternWithDotSegmentThrows, testRequestPathWithTraversalDoesNotMatch, testParametersAreUrlDecodedExactlyOnce, testDoubleEncodedPathDoesNotDoubleDecode |
| `tests/Unit/AttributeRouteLoaderTest.php` | AttributeRouteLoaderTest | testLoadExtractsRoutesFromAttributes, testLoadedRouteHasCorrectControllerInfo, testLoadedRouteHasConstraints, testLoadedRouteHasMiddleware, testLoadSkipsNonExistentClass |
| `tests/Unit/RouteCollectionTest.php` | RouteCollectionTest | testAddRoute, testDuplicateNameThrows, testAnonymousRoutesAllowed, testGetByNameThrowsForMissing, testGetByMethod (+3 more) |
| `tests/Unit/RouteCompilerTest.php` | RouteCompilerTest | testNoPlaceholderPath, testSinglePlaceholder, testMultiplePlaceholders, testInlineConstraint, testConstraintsMapOverride (+7 more) |
| `tests/Unit/RouterTest.php` | RouterTest | testAddRouteAndMatch, testMatchReturnsNullOnNoMatch, testMatchReturnsNullOnMethodMismatch, testMatchExtractsParameters, testMatchUrlDecodesParametersOnce (+11 more) |

### CORE-07 — NeoSPX Lexer

**Test files:** 1

| Test File | Class Under Test | Behaviours |
|---|---|---|
| `tests/Unit/Lexer/NeoSPXLexerTest.php` | NeoSPXLexerTest | testSimpleComponentTag, testPairedComponentTags, testRawHtmlPassthrough, testOutputExpression, testRawOutputExpression (+22 more) |

### CORE-08 — Global Error & Exception Handler

**Test files:** 2

| Test File | Class Under Test | Behaviours |
|---|---|---|
| `tests/Unit/ErrorHandlerTest.php` | ErrorHandlerTest | testRegisterSetsDisplayErrorsOff, testUnregisterRestoresDisplayErrors, testRegisterIsIdempotent, testUnregisterIsIdempotent, testHandleExceptionLogsAtErrorLevel (+13 more) |
| `tests/Unit/RendererTest.php` | RendererTest | testJsonRendererContentType, testPlainTextRendererContentType, testJsonRendererDebugModeProducesFullOutput, testJsonRendererProductionModeHidesDetails, testJsonRendererMapsOutOfBoundsExceptionToNotFound (+7 more) |

### CORE-09 — PSR-3 Logging Service

**Test files:** 8

| Test File | Class Under Test | Behaviours |
|---|---|---|
| `tests/Performance/LoggerBenchTest.php` | LoggerBenchTest | testSingleLogCallUnder100Microseconds, testFilteredLogCallUnderTenMicroseconds, testFormatterFormatUnder20Microseconds, testBatchWriteFasterThanIndividualWrites |
| `tests/Unit/JsonFormatterTest.php` | JsonFormatterTest | testFormatProducesValidJson, testFormatInterpolatesPlaceholders, testFormatMergesContextIntoTopLevel, testFormatMergesExtraIntoTopLevel, testFormatSuffixesCollidingContextKeys (+4 more) |
| `tests/Unit/LineFormatterLogInjectionTest.php` | LineFormatterLogInjectionTest | testNewlinesInMessageAreEscaped, testCarriageReturnsInMessageAreEscaped, testNewlinesInContextValuesAreEscaped, testTabsAreEscaped, testOtherControlCharsAreEscaped (+1 more) |
| `tests/Unit/LineFormatterTest.php` | LineFormatterTest | testFormatProducesBaselineShape, testFormatInterpolatesPlaceholders, testFormatLeavesUnknownPlaceholdersAsIs, testFormatAppendsContextWhenPresent, testFormatOmitsEmptyContext (+6 more) |
| `tests/Unit/LogRecordTest.php` | LogRecordTest | testCreateProducesRecordWithCurrentTimestamp, testCreateCastsStringableMessageToString, testConstructorRejectsInvalidLevel, testIsAtLeastReturnsTrueForEqualSeverity, testIsAtLeastReturnsTrueForHigherSeverity (+8 more) |
| `tests/Unit/LoggerTest.php` | LoggerTest | testLogWritesToRegisteredHandler, testAllPsr3LevelsAreAccepted, testThresholdFiltersLowLevelRecords, testThresholdReturnsCurrentLevel, testWithHandlerReturnsNewInstance (+10 more) |
| `tests/Unit/RedactingFormatterTest.php` | RedactingFormatterTest | testAlwaysRedactKeysAreRedacted, testAdditionalRedactKeysAreRedacted, testAlwaysRedactCannotBeRemoved, testNestedArrayKeysAreRedacted, testCaseInsensitiveRedaction (+3 more) |
| `tests/Unit/StreamHandlerTest.php` | StreamHandlerTest | testHandleWritesFormattedLineToFile, testHandleAppendsToExistingFile, testIsHandlingReturnsTrueForRecordsAtOrAboveThreshold, testIsHandlingReturnsFalseAfterClose, testHandleReturnsFalseWhenNotHandling (+8 more) |

### CORE-10 — Configuration & Environment Loader

**Test files:** 8

| Test File | Class Under Test | Behaviours |
|---|---|---|
| `tests/Fixtures/config/app.php` | (unknown) |  |
| `tests/Fixtures/config/local.php` | (unknown) |  |
| `tests/Fixtures/config/not_array.php` | (unknown) |  |
| `tests/Performance/ConfigBenchTest.php` | ConfigBenchTest | testNestedKeyResolutionUnderTenMicroseconds, testHasCheckUnderTenMicroseconds, testMissLookupUnderTenMicroseconds, testBuilderBuildTimeAcceptableForLargeConfig |
| `tests/Security/ConfigSecurityTest.php` | ConfigSecurityTest | testEnvKeyWithDotDoesNotEscapeNamespace, testOverrideKeyCannotTraverseUpViaEmptySegments, testOverrideKeyCannotBeEmpty, testGetWithEmptyKeyThrows, testConfigValuesAreNotExecutedAsStringEval (+2 more) |
| `tests/Unit/ConfigBuilderTest.php` | ConfigBuilderTest | testLoadFileMergesIntoRepository, testLoadFileMergesRecursivelyOnKeyCollision, testLoadFileThrowsWhenFileMissing, testLoadFileThrowsWhenFileDoesNotReturnArray, testWithOverrideSetsNestedKey (+11 more) |
| `tests/Unit/ConfigRepositoryTest.php` | ConfigRepositoryTest | testGetReturnsValueForTopLevelKey, testGetReturnsValueForNestedKeyUsingDotNotation, testGetReturnsDefaultWhenKeyAbsent, testGetReturnsDefaultWhenIntermediateSegmentIsNotArray, testGetOrFailReturnsValueWhenKeyPresent (+18 more) |
| `tests/Unit/EnvLoaderTest.php` | EnvLoaderTest | testLoadParsesKeyValuePairs, testLoadWritesToEnvSuperglobal, testLoadInterpolatesDoubleQuotedValues, testLoadInterpolatesReferencesToExistingEnvVars, testLoadDoesNotInterpolateSingleQuotedValues (+12 more) |

### CORE-11 — NeoSPX Parser

**Test files:** 1

| Test File | Class Under Test | Behaviours |
|---|---|---|
| `tests/Unit/Lexer/NeoSPXLexerTest.php` | NeoSPXLexerTest | testSimpleComponentTag, testPairedComponentTags, testRawHtmlPassthrough, testOutputExpression, testRawOutputExpression (+22 more) |

### CORE-12 — NeoSPX Compiler

**Test files:** 1

| Test File | Class Under Test | Behaviours |
|---|---|---|
| `tests/Unit/Lexer/NeoSPXLexerTest.php` | NeoSPXLexerTest | testSimpleComponentTag, testPairedComponentTags, testRawHtmlPassthrough, testOutputExpression, testRawOutputExpression (+22 more) |

### CORE-14 — Filesystem Abstraction

**Test files:** 2

| Test File | Class Under Test | Behaviours |
|---|---|---|
| `tests/Unit/FilesystemDepth3Test.php` | FilesystemDepth3Test | testRootPrefixCollisionRejected, testValidPathsWithinRootAccepted, testNullByteRejected, testAbsolutePathRejected, testParentDirectoryTraversalRejected (+7 more) |
| `tests/Unit/FilesystemTest.php` | FilesystemTest | testWriteAndReadRoundTrip, testWriteReturnsFileMetadata, testReadReturnsNullForNonExistent, testExists, testDeleteIsIdempotent (+8 more) |

### CORE-16 — Binary Encryption Envelope

**Test files:** 4

| Test File | Class Under Test | Behaviours |
|---|---|---|
| `tests/Unit/CryptoExceptionTest.php` | CryptoExceptionTest | testTagMismatchErrorCode, testUnknownKidErrorCode, testWeakHashParametersErrorCode, testExtendsRuntimeException |
| `tests/Unit/HasherTest.php` | HasherTest | testDeriveKeyDeterministic, testDifferentInfoProducesDifferentKeys, testCustomLength |
| `tests/Unit/KeyRegistryTest.php` | KeyRegistryTest | testAddAndGetKey, testFirstKeyBecomesActive, testSetActiveKey, testDeactivateKey, testDeactivateActiveKeyThrows (+4 more) |
| `tests/Unit/PasswordHasherTest.php` | PasswordHasherTest | testHashProducesArgon2id, testVerifyCorrectPassword, testVerifyWrongPassword, testNeedsRehashReturnsFalseAtDefaultParams, testWeakParametersRejected |

### CORE-18 — Core Kernel & Lifecycle

**Test files:** 6

| Test File | Class Under Test | Behaviours |
|---|---|---|
| `tests/Fixtures/HelloWorldController.php` | HelloWorldController |  |
| `tests/Integration/HelloWorldTest.php` | HelloWorldTest | testHelloWorldRoundTrip, testMultipleRequestsInSequence, testUnmatchedRouteReturns404, testCannotHandleAfterTerminate, testBootEventIsDispatched (+1 more) |
| `tests/Integration/WorkerContaminationTest.php` | WorkerContaminationTest | testConcurrentFibersObserveIndependentPulseState, testSequentialRequestsOnOneWorkerDoNotInheritPriorState, testExceptionDuringRequestDoesNotLeakToSubsequentRequest, testCompletedFiberStateIsNotVisibleToNewFiber, testRepeatedWorkerReuseMaintainsIsolationAcrossFiveRequests (+6 more) |
| `tests/Unit/KernelStateMachineTest.php` | KernelStateMachineTest | testInitialstateIsUnbooted, testBootTransitionsToBooted, testHandleTransitionsToHandlingAndBack, testTerminateTransitionsToTerminated, testBootAfterTerminateThrows (+31 more) |
| `tests/Unit/RequestContextTest.php` | RequestContextTest | testConstructionStoresAllFields, testRouteNameDefaultsToNullWhenOmitted, testTenantIdMayBeNullForUnauthenticatedRequest, testEmptyStringTenantIdTreatedAsAbsent, testWithTenantIdReturnsNewInstanceNotMutatingOriginal (+9 more) |
| `tests/Unit/TestKernelFactory.php` | TestKernelFactory |  |

### CORE-19 — Database Abstraction Layer

**Test files:** 6

| Test File | Class Under Test | Behaviours |
|---|---|---|
| `tests/Unit/ConnectionTest.php` | ConnectionTest | testExecReturnsAffectedRows, testQueryReturnsStatement, testPrepareReturnsCachedStatement, testPrepareDifferentSqlReturnsDifferentStatements, testLastInsertId (+7 more) |
| `tests/Unit/DriverTest.php` | DriverTest | testMysqlDriverName, testMysqlDriverSupports, testMysqlDriverQuoteIdentifier, testSqliteDriverName, testSqliteDriverSupports (+2 more) |
| `tests/Unit/Migration/DoctrineMigrationRunnerEnvTest.php` | DoctrineMigrationRunnerEnvTest | testEnvStringReturnsDefaultWhenVariableAbsent, testEnvStringReturnsDefaultForAllStringKeysWhenAbsent, testEnvStringReturnsValueFromEnvSuperglobal, testEnvStringReturnsValueFromGetenvWhenNotInSuperglobal, testEnvStringSuperglobalPrecedenceOverGetenv (+10 more) |
| `tests/Unit/Migration/DoctrineMigrationRunnerTest.php` | DoctrineMigrationRunnerTest | testImplementsInterface, testStatusReturnsAllMigrations, testFreshInstallAppliesAllMigrations, testIdempotentRerun, testRollbackThrows |
| `tests/Unit/QueryBuilderTest.php` | QueryBuilderTest | testSelectAllFromTable, testSelectSpecificColumns, testWhereEquals, testWhereWithOr, testOrderBy (+12 more) |
| `tests/Unit/TypeMapperTest.php` | TypeMapperTest | testBoolToSql, testNullToSql, testStringToSql, testIntToSql, testDateTimeToSql (+5 more) |

## Summary

- **Total test files:** 89
- **Total test classes:** 89
- **Components with tests:** 15
- **Components without tests:** 2

Note: Test method counts are based on `public function test*()` pattern matching in source.
Actual test execution was NOT performed — this is source inspection only.
