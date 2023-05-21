<?php

namespace Ecommage\FixValidateCheckout\Plugin\Webapi;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\SimpleDataObjectConverter;
use Magento\Framework\Exception\SerializationException;
use Magento\Framework\ObjectManager\ConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Reflection\MethodsMap;
use Magento\Framework\Reflection\TypeProcessor;
use Magento\Framework\Webapi\CustomAttributeTypeLocatorInterface;
use Magento\Framework\Webapi\ServiceTypeToEntityTypeMap;

if (class_exists('\Laminas\Code\Reflection\ClassReflection')) {
    class MiddleClass extends \Laminas\Code\Reflection\ClassReflection { }
} elseif (!class_exists('\Laminas\Code\Reflection\ClassReflection')) {
    class MiddleClass extends \Zend\Code\Reflection\ClassReflection { }
}

class ServiceInput extends \Ecommage\Address\Preference\Webapi\ServiceInputProcessor
{
    /**
     * @var \Magento\Framework\Reflection\NameFinder
     */
    private $nameFinder;

    /**
     * @var array
     */
    private $serviceTypeToEntityTypeMap;

    /**
     * @var ConfigInterface
     */
    protected $_config;

    /**
     * @param TypeProcessor $typeProcessor
     * @param ObjectManagerInterface $objectManager
     * @param AttributeValueFactory $attributeValueFactory
     * @param CustomAttributeTypeLocatorInterface $customAttributeTypeLocator
     * @param MethodsMap $methodsMap
     * @param ServiceTypeToEntityTypeMap|null $serviceTypeToEntityTypeMap
     * @param ConfigInterface|null $config
     * @param array $customAttributePreprocessors
     */
    public function __construct(
        TypeProcessor $typeProcessor,
        ObjectManagerInterface $objectManager,
        AttributeValueFactory $attributeValueFactory,
        CustomAttributeTypeLocatorInterface $customAttributeTypeLocator,
        MethodsMap $methodsMap,
        ServiceTypeToEntityTypeMap $serviceTypeToEntityTypeMap = null,
        ConfigInterface $config = null,
        array $customAttributePreprocessors = [])
    {
        parent::__construct($typeProcessor, $objectManager, $attributeValueFactory, $customAttributeTypeLocator, $methodsMap, $serviceTypeToEntityTypeMap, $config, $customAttributePreprocessors);
    }

    /**
     * Creates a new instance of the given class and populates it with the array of data. The data can
     * be in different forms depending on the adapter being used, REST vs. SOAP. For REST, the data is
     * in snake_case (e.g. tax_class_id) while for SOAP the data is in camelCase (e.g. taxClassId).
     *
     * @param string $className
     * @param array $data
     * @return object the newly created and populated object
     * @throws \Exception
     * @throws SerializationException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _createFromArray($className, $data)
    {
        $data = is_array($data) ? $data : [];
        // convert to string directly to avoid situations when $className is object
        // which implements __toString method like \ReflectionObject
        $className = (string) $className;
        $class = new MiddleClass($className);
        if (is_subclass_of($className, self::EXTENSION_ATTRIBUTES_TYPE)) {
            $className = substr($className, 0, -strlen('Interface'));
        }

        // Primary method: assign to constructor parameters
        $constructorArgs = $this->getConstructorData($className, $data);
        $object = $this->objectManager->create($className, $constructorArgs);

        if(!empty($data['customAttributes'])){
            foreach ($data['customAttributes'] as $customAttributes){
                unset($data[$customAttributes['attribute_code']]);
            }
        }
        unset($data['city_id']);
        unset($data['ward']);
        unset($data['ward_id']);

        // Secondary method: fallback to setter methods
        foreach ($data as $propertyName => $value) {
            if (isset($constructorArgs[$propertyName])) {
                continue;
            }
            // Converts snake_case to uppercase CamelCase to help form getter/setter method names
            // This use case is for REST only. SOAP request data is already camel cased
            $camelCaseProperty = SimpleDataObjectConverter::snakeCaseToUpperCamelCase($propertyName);
            $methodName = $this->getNameFinder()->getGetterMethodName($class, $camelCaseProperty);
            $methodReflection = $class->getMethod($methodName);
            if ($methodReflection->isPublic()) {
                $returnType = $this->typeProcessor->getGetterReturnType($methodReflection)['type'];
                try {
                    $setterName = $this->getNameFinder()->getSetterMethodName($class, $camelCaseProperty);
                } catch (\Exception $e) {
                    if (empty($value)) {
                        continue;
                    } else {
                        throw $e;
                    }
                }
                try {
                    if ($camelCaseProperty === 'CustomAttributes') {
                        if ($className == "Magento\Quote\Api\Data\AddressInterface") {
                            $setterName = "setExtensionAttributes";
                            $setterValue = $this->convertCustomAttributeToExtensionAttribute($value, $className);
                        } else {
                            $setterValue = $this->convertCustomAttributeValue($value, $className);
                        }
                    } else {
                        $setterValue = $this->convertValue($value, $returnType);
                    }
                } catch (SerializationException $e) {
                    throw new SerializationException(
                        new Phrase(
                            'Error occurred during "%field_name" processing. %details',
                            ['field_name' => $propertyName, 'details' => $e->getMessage()]
                        )
                    );
                }
                $object->{$setterName}($setterValue);
            }
        }

        return $object;
    }

    /**
     *
     * @param array $customAttributesValueArray
     * @param string $dataObjectClassName
     */
    public function convertCustomAttributeToExtensionAttribute($customAttributesValueArray, $dataObjectClassName)
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magento\Framework\Api\SimpleDataObjectConverter $simpleDataObjectConverter */
        $simpleDataObjectConverter = $om->get(\Magento\Framework\Api\SimpleDataObjectConverter::class);
        $extensionAttributesFactory = $om->get(\Magento\Framework\Api\ExtensionAttributesFactory::class);
        $extensionAttributes = $extensionAttributesFactory->create($dataObjectClassName);

        foreach ($customAttributesValueArray as $customAttribute) {
            $camelCaseKey = $simpleDataObjectConverter->snakeCaseToUpperCamelCase($customAttribute['attribute_code']);
            $setterMethod = 'set' . $camelCaseKey;
            $extensionAttributes->$setterMethod(isset($customAttribute['value']) ? $customAttribute['value'] : null);
        }
        return $extensionAttributes;
    }

    /**
     * Retrieve constructor data
     *
     * @param string $className
     * @param array $data
     * @return array
     * @throws \ReflectionException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getConstructorData(string $className, array $data): array
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var ConfigInterface $configInterface */
        $configInterface = $om->get(ConfigInterface::class);
        $preferenceClass = $configInterface->getPreference($className);
        $class = new MiddleClass($preferenceClass ?: $className);

        try {
            $constructor = $class->getMethod('__construct');
        } catch (\ReflectionException $e) {
            $constructor = null;
        }

        if ($constructor === null) {
            return [];
        }

        $res = [];
        $parameters = $constructor->getParameters();
        foreach ($parameters as $parameter) {
            if (isset($data[$parameter->getName()])) {
                $parameterType = $this->typeProcessor->getParamType($parameter);

                try {
                    $res[$parameter->getName()] = $this->convertValue($data[$parameter->getName()], $parameterType);
                } catch (\ReflectionException $e) {
                    // Parameter was not correclty declared or the class is uknown.
                    // By not returing the contructor value, we will automatically fall back to the "setters" way.
                    continue;
                }
            }
        }

        return $res;
    }

    /**
     * The getter function to get the new NameFinder dependency
     *
     * @return \Magento\Framework\Reflection\NameFinder
     *
     * @deprecated 100.1.0
     */
    private function getNameFinder()
    {
        if ($this->nameFinder === null) {
            $this->nameFinder = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Reflection\NameFinder::class);
        }
        return $this->nameFinder;
    }
}
